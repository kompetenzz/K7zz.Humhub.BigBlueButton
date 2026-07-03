<?php

namespace k7zz\humhub\bbb\models;

use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use humhub\modules\user\models\User;
use Yii;

/**
 * One chat message, either pre-meeting (session_meeting_id = null) or in-meeting.
 *
 * Pre-meeting (queued by HumHub user before the meeting starts):
 *   session_id = X, session_meeting_id = null, source = 'humhub', sent_at = null
 *
 * In-meeting from HumHub (injected into BBB on meeting start):
 *   session_id = X, session_meeting_id = M, source = 'humhub', sent_at = timestamp
 *
 * From BBB via webhook:
 *   session_meeting_id = M, source = 'bbb', sent_at = timestamp
 *
 * @property int         $id
 * @property int|null    $session_id          set for pre-meeting messages
 * @property int|null    $user_id_queued       HumHub user who queued the message (pre-meeting)
 * @property int|null    $session_meeting_id
 * @property int|null    $user_id             HumHub user (null for external/anonymous, resolved via BBB join)
 * @property string      $sender_name
 * @property string      $message
 * @property string      $source              humhub|bbb
 * @property int|null    $sent_at             null = pending injection
 * @property int         $created_at
 */
class SessionMeetingChat extends ActiveRecord
{
    public const SOURCE_HUMHUB = 'humhub';
    public const SOURCE_BBB    = 'bbb';

    public static function tableName(): string
    {
        return 'bbb_session_meeting_chat';
    }

    public function rules(): array
    {
        return [
            [['message', 'source'], 'required'],
            [['session_id', 'session_meeting_id', 'user_id', 'user_id_queued', 'sent_at', 'created_at'], 'integer'],
            [['sender_name'], 'string', 'max' => 255],
            [['source'], 'in', 'range' => [self::SOURCE_HUMHUB, self::SOURCE_BBB]],
            [['message'], 'string'],
        ];
    }

    public function beforeSave($insert): bool
    {
        if ($insert && !$this->created_at) {
            $this->created_at = time();
        }
        return parent::beforeSave($insert);
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getQueuedByUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id_queued']);
    }

    public function getMeeting(): ActiveQuery
    {
        return $this->hasOne(SessionMeeting::class, ['id' => 'session_meeting_id']);
    }

    public function getSession(): ActiveQuery
    {
        return $this->hasOne(Session::class, ['id' => 'session_id']);
    }

    /**
     * Returns all pending HumHub messages for a session (not yet assigned to a meeting).
     */
    public static function findPendingForSession(int $sessionId): ActiveQuery
    {
        return static::find()->where([
            'session_id'          => $sessionId,
            'session_meeting_id'  => null,
            'source'              => self::SOURCE_HUMHUB,
            'sent_at'             => null,
        ])->orderBy(['created_at' => SORT_ASC]);
    }

    /**
     * Returns all chat messages for a session that are visible in the pre-meeting view.
     * Shows queued (pending) HumHub messages only.
     */
    public static function findPreMeetingForSession(int $sessionId): ActiveQuery
    {
        return static::find()
            ->where(['session_id' => $sessionId, 'session_meeting_id' => null])
            ->orderBy(['created_at' => SORT_ASC]);
    }

    /**
     * Returns all messages for a session across all meetings (pre-meeting + in-meeting + BBB-received).
     */
    public static function findAllForSession(int $sessionId, int $limit = 300): ActiveQuery
    {
        return static::find()
            ->where(['session_id' => $sessionId])
            ->orderBy(['created_at' => SORT_ASC, 'id' => SORT_ASC])
            ->limit($limit);
    }
}
