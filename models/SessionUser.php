<?php
namespace k7zz\humhub\bbb\models;

use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use humhub\modules\user\models\User;

/**
 * ActiveRecord model for the relation between BBB sessions and users.
 *
 * Represents user-specific permissions and roles for a session.
 *
 * @property int $session_id
 * @property int $user_id
 * @property bool $can_start
 * @property bool $can_join
 * @property string $role
 */
class SessionUser extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return 'bbb_session_user';
    }

    /**
     * Validation rules for the model.
     * @return array
     */
    public function rules(): array
    {
        return [
            [['session_id', 'user_id'], 'required'],
            [['session_id', 'user_id'], 'integer'],
            [['can_start', 'can_join'], 'boolean'],
            [['role'], 'in', 'range' => ['moderator', 'attendee']],
        ];
    }

    /**
     * Gets the related session.
     * @return ActiveQuery
     */
    public function getSession(): ActiveQuery
    {
        return $this->hasOne(Session::class, ['id' => 'session_id']);
    }

    /**
     * Gets the related user.
     * @return ActiveQuery
     */
    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}