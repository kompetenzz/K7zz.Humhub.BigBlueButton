<?php

namespace k7zz\humhub\bbb\models;

use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use humhub\modules\user\models\User;

/**
 * Join/leave event for a user within a session meeting run.
 *
 * bbb_internal_user_id: BBB's internal "w_abc123" ID (used to match chat messages to users)
 * user_id: resolved HumHub user (via externalUserId = HumHub user.id set in joinUrl())
 *
 * @property int         $id
 * @property int         $session_meeting_id
 * @property int|null    $user_id
 * @property string      $bbb_internal_user_id
 * @property string      $display_name
 * @property string      $role                  moderator|viewer
 * @property int         $joined_at
 * @property int|null    $left_at
 */
class SessionMeetingJoin extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'bbb_session_meeting_join';
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getMeeting(): ActiveQuery
    {
        return $this->hasOne(SessionMeeting::class, ['id' => 'session_meeting_id']);
    }

    public static function findByInternalUserId(int $sessionMeetingId, string $bbbInternalUserId): ?self
    {
        return static::findOne([
            'session_meeting_id'   => $sessionMeetingId,
            'bbb_internal_user_id' => $bbbInternalUserId,
        ]);
    }
}
