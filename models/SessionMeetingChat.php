<?php

namespace k7zz\humhub\bbb\models;

use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use humhub\modules\user\models\User;

/**
 * One chat message within a session meeting run.
 *
 * source = 'humhub': written in HumHub before/during meeting
 *   sent_at = null  → not yet injected into BBB
 *   sent_at = int   → injected at that timestamp
 * source = 'bbb': received via webhook from BBB chat
 *
 * @property int         $id
 * @property int         $session_meeting_id
 * @property int|null    $user_id             HumHub user (null for external/anonymous)
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

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getMeeting(): ActiveQuery
    {
        return $this->hasOne(SessionMeeting::class, ['id' => 'session_meeting_id']);
    }
}
