<?php

use humhub\modules\content\models\Content;
use k7zz\humhub\bbb\models\Session;
use yii\db\Migration;

/**
 * Takes the wall entries of already deleted sessions out of the streams.
 *
 * Deleting a session used to set `bbb_session.deleted_at` only and never touched
 * the content record. The session disappeared from the module's own lists while its
 * wall entry stayed published on the dashboard, in the container's stream and in the
 * search index - comments, likes and files included.
 *
 * This brings existing installations in line with the fixed delete: every session
 * that is marked as deleted gets its content soft-deleted as well. HumHub's
 * PurgeDeletedContents cron job then removes those sessions for good on its next
 * daily run.
 *
 * Deliberately `up()` instead of `safeUp()`: a single failing record must not roll
 * back - and thereby abort - the whole module update.
 */
class m260918_010000_bbb_delete_content_of_deleted_sessions extends Migration
{
    public function up()
    {
        if ($this->db->getTableSchema('bbb_session', true) === null) {
            // Fresh install - there is nothing to clean up yet.
            return;
        }

        $sessions = Session::find()
            ->alias('session')
            ->joinWith('content')
            ->where(['not', ['session.deleted_at' => null]])
            ->andWhere(['content.state' => Content::STATE_PUBLISHED])
            ->all();

        if ($sessions === []) {
            return;
        }

        foreach ($sessions as $session) {
            try {
                if (!$session->softDelete()) {
                    echo '  Could not delete the content of session ' . $session->id . "\n";
                }
            } catch (Throwable $e) {
                echo '  Could not delete the content of session ' . $session->id . ': ' . $e->getMessage() . "\n";
            }
        }

        echo '  Removed the wall entries of ' . count($sessions) . " deleted session(s)\n";
    }

    public function down()
    {
        echo "m260918_010000_bbb_delete_content_of_deleted_sessions cannot be reverted.\n";

        return false;
    }
}
