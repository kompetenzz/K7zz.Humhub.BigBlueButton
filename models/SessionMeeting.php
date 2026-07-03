<?php

namespace k7zz\humhub\bbb\models;

use yii\db\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * One run of a BBB session (meeting-started → meeting-ended).
 *
 * @property int         $id
 * @property int         $session_id
 * @property string      $internal_meeting_id   BBB-internal ID (changes per run)
 * @property int         $started_at
 * @property int|null    $ended_at
 * @property int         $created_at
 */
class SessionMeeting extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'bbb_session_meeting';
    }

    public static function findByInternalId(string $internalMeetingId): ?self
    {
        return static::findOne(['internal_meeting_id' => $internalMeetingId]);
    }

    public function getChats(): ActiveQuery
    {
        return $this->hasMany(SessionMeetingChat::class, ['session_meeting_id' => 'id'])
            ->orderBy(['created_at' => SORT_ASC]);
    }

    public function getJoins(): ActiveQuery
    {
        return $this->hasMany(SessionMeetingJoin::class, ['session_meeting_id' => 'id']);
    }

    public function getSession(): ActiveQuery
    {
        return $this->hasOne(Session::class, ['id' => 'session_id']);
    }

    public function getPendingChats(): ActiveQuery
    {
        return $this->hasMany(SessionMeetingChat::class, ['session_meeting_id' => 'id'])
            ->andWhere(['source' => 'humhub', 'sent_at' => null]);
    }
}
