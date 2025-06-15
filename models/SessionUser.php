<?php
namespace k7zz\humhub\bbb\models;

use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use humhub\modules\user\models\User;

class SessionUser extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'bbb_session_user';
    }
    public function rules(): array
    {
        return [
            [['session_id', 'user_id'], 'required'],
            [['session_id', 'user_id'], 'integer'],
            [['can_start', 'can_join'], 'boolean'],
            [['role'], 'in', 'range' => ['moderator', 'attendee']],
        ];
    }
    public function getSession(): ActiveQuery
    {
        return $this->hasOne(Session::class, ['id' => 'session_id']);
    }
    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}