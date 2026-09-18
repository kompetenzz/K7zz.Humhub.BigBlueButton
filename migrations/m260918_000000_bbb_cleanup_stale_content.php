<?php

use humhub\modules\activity\models\Activity;
use humhub\modules\comment\models\Comment;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\file\models\File;
use humhub\modules\like\models\Like;
use humhub\modules\notification\models\Notification;
use humhub\modules\user\models\Follow;
use humhub\modules\user\models\Mentioning;
use k7zz\humhub\bbb\models\Session;
use k7zz\humhub\bbb\models\SessionChatReaction;
use k7zz\humhub\bbb\models\SessionMeetingChat;
use yii\db\Migration;
use yii\db\Query;

/**
 * Removes records that point to sessions which no longer exist.
 *
 * Until now the module implemented neither `disable()` nor
 * `disableContentContainer()`. Disabling or uninstalling it therefore dropped the
 * `bbb_*` tables but left every session's `content` record behind, along with the
 * comments, likes, follows, mentions, files, activities and notifications attached
 * to it. All of those still reference session ids that are gone.
 *
 * Cleaning them up is not just cosmetic: dropping `bbb_session` resets its
 * AUTO_INCREMENT, so after a reinstall the leftovers start resolving to unrelated
 * new sessions - an old presentation or an old comment would show up on a stranger's
 * session. They therefore have to go before new sessions reuse those ids.
 *
 * Deletion goes through the models rather than `deleteAll()`, so their own cleanup
 * runs as well (physical files are unlinked, an activity's own content record goes
 * with it, and so on). Whatever is left afterwards is generic residue that HumHub's
 * `php yii integrity` reports and fixes.
 *
 * Deliberately `up()` instead of `safeUp()`: a single failing record must not roll
 * back - and thereby abort - the whole module update.
 */
class m260918_000000_bbb_cleanup_stale_content extends Migration
{
    /**
     * Records attached to a session, as [class, model column, id column].
     */
    private const SESSION_ATTACHMENTS = [
        [Comment::class, 'object_model', 'object_id'],
        [Like::class, 'object_model', 'object_id'],
        [Follow::class, 'object_model', 'object_id'],
        [Mentioning::class, 'object_model', 'object_id'],
        [File::class, 'object_model', 'object_id'],
        [Activity::class, 'object_model', 'object_id'],
        [Content::class, 'object_model', 'object_id'],
    ];

    /**
     * Everything this module sends notifications about, as [class => table].
     */
    private const NOTIFICATION_SOURCES = [
        Session::class => 'bbb_session',
        SessionMeetingChat::class => 'bbb_session_meeting_chat',
        SessionChatReaction::class => 'bbb_session_chat_reaction',
    ];

    public function up()
    {
        if ($this->db->getTableSchema('bbb_session', true) === null) {
            // Fresh install - there is nothing to clean up yet.
            return;
        }

        // Every id still present in bbb_session belongs to a live session. On a
        // reinstall the table is empty, so every leftover is stale.
        $liveSessionIds = (new Query())->select('id')->from('bbb_session');

        foreach (self::SESSION_ATTACHMENTS as [$class, $modelColumn, $idColumn]) {
            $this->deleteStale($class, $modelColumn, $idColumn, Session::class, $liveSessionIds);
        }

        foreach (self::NOTIFICATION_SOURCES as $sourceClass => $sourceTable) {
            if ($this->db->getTableSchema($sourceTable, true) === null) {
                continue;
            }

            $this->deleteStale(
                Notification::class,
                'source_class',
                'source_pk',
                $sourceClass,
                (new Query())->select('id')->from($sourceTable),
            );
        }

        $this->deleteDuplicateContent();
    }

    public function down()
    {
        echo "m260918_000000_bbb_cleanup_stale_content cannot be reverted.\n";

        return false;
    }

    /**
     * Removes duplicate content records of live sessions.
     *
     * Dropping `bbb_session` resets its AUTO_INCREMENT, so on a reinstall a leftover
     * content record can end up sharing its session id with a newly created session.
     * HumHub never creates more than one content record per record, so where there
     * is more than one only the newest - the one that was created together with the
     * current session - is genuine; the older ones are leftovers of a session that no
     * longer exists and would show the new session in the wrong container.
     */
    private function deleteDuplicateContent(): void
    {
        $duplicateSessionIds = (new Query())
            ->select('object_id')
            ->from('content')
            ->where(['object_model' => Session::class])
            ->groupBy('object_id')
            ->having('COUNT(*) > 1')
            ->column($this->db);

        foreach ($duplicateSessionIds as $sessionId) {
            $contents = Content::find()
                ->where(['object_model' => Session::class, 'object_id' => $sessionId])
                ->orderBy(['id' => SORT_DESC])
                ->all();

            // Keep the newest one.
            foreach (array_slice($contents, 1) as $content) {
                try {
                    if (!$content->hardDelete()) {
                        echo '  Could not delete duplicate content ' . $content->id . ' of session ' . $sessionId . "\n";
                        continue;
                    }

                    echo '  Removed duplicate content ' . $content->id . ' of session ' . $sessionId . "\n";
                } catch (Throwable $e) {
                    echo '  Could not delete duplicate content ' . $content->id . ': ' . $e->getMessage() . "\n";
                }
            }
        }
    }

    /**
     * Deletes all records of `$class` that reference `$objectModel` with an id that
     * is no longer present in the source table.
     *
     * @param class-string<\yii\db\ActiveRecord> $class
     * @param Query $liveIds subquery selecting the ids that still exist
     */
    private function deleteStale(
        string $class,
        string $modelColumn,
        string $idColumn,
        string $objectModel,
        Query $liveIds,
    ): void {
        $records = $class::find()
            ->where([$modelColumn => $objectModel])
            ->andWhere(['not in', $idColumn, $liveIds])
            ->all();

        if ($records === []) {
            return;
        }

        foreach ($records as $record) {
            try {
                // Content records - an Activity, for instance - soft-delete by default.
                $deleted = $record instanceof ContentActiveRecord || $record instanceof Content
                    ? $record->hardDelete()
                    : $record->delete();

                if ($deleted === false) {
                    echo '  Could not delete stale ' . $class . ' id ' . $record->id . "\n";
                }
            } catch (Throwable $e) {
                echo '  Could not delete stale ' . $class . ' id ' . $record->id . ': ' . $e->getMessage() . "\n";
            }
        }

        echo '  Removed ' . count($records) . ' stale ' . $class . " record(s)\n";
    }
}
