<?php
namespace humhub\modules\bbb\models;
use Yii;
use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use humhub\modules\file\converter\PreviewImage;
use humhub\modules\file\models\File;
use humhub\modules\user\models\User;
use humhub\modules\bbb\permissions\{
    Admin,
    StartSession,
    JoinSession
};
/**
 * @property int    $id
 * @property string $uuid
 * @property string $moderator_pw
 * …
 */
class Session extends ActiveRecord
{
    public $outputImage = null;

    public function afterFind()
    {
        parent::afterFind();
        if ($this->image_file_id !== null) {
            $image = $this->getImageFile()->one();
            $previewImage = new PreviewImage();
            if ($previewImage->applyFile($image)) {
                $this->outputImage = $previewImage;
            }
        }
    }
    public static function tableName(): string
    {
        return 'bbb_session';
    }

    public function canAdminister(?User $user = null): bool
    {
        $user ??= Yii::$app->user;

        if ($this->contentcontainer_id === null) {
            if ($user->can(Admin::class))
                return true;
        } else {
            $container = $this->getContentContainer();
            if ($container && $container->can(Admin::class))
                return true;
        }
        return false;
    }

    /** darf $user diese Session starten? */
    public function canStart(?User $user = null): bool
    {
        $user ??= Yii::$app->user;

        // 1) Globale bzw. Container-Permission
        if ($this->contentcontainer_id === null) {
            if ($user->can(StartSession::class))
                return true;
        } else {
            $container = $this->getContentContainer();                // Space/User-Profil
            if ($container && $container->can(StartSession::class))
                return true;
        }

        // 2) Pivot-Zeile
        $pivot = SessionUser::findOne(['session_id' => $this->id, 'user_id' => $user->id]);
        return $pivot ? (bool) $pivot->can_start || $pivot->role === 'moderator' : false;
    }

    /** darf $user beitreten? */
    public function canJoin(?User $user = null): bool
    {
        $user ??= Yii::$app->user;
        if ($this->contentcontainer_id === null) {
            if ($user->can(JoinSession::class))
                return true;
        } else {
            $container = $this->getContentContainer();
            if ($container && $container->can(JoinSession::class))
                return true;
        }

        if ($this->canStart($user)) {
            return true; //  dürfen immer beitreten
        }

        $pivot = SessionUser::findOne(['session_id' => $this->id, 'user_id' => $user->id]);
        return $pivot ? (bool) $pivot->can_join : false;
    }

    public function isModerator(?User $user = null): bool
    {
        $user ??= Yii::$app->user;
        if ($this->contentcontainer_id === null) {
            if ($user->can(Admin::class))
                return true;
        } else {
            $container = $this->getContentContainer();
            if ($container && $container->can(Admin::class))
                return true;
        }

        $pivot = SessionUser::findOne(['session_id' => $this->id, 'user_id' => $user->id]);
        return $pivot ? (bool) $pivot->role === 'moderator' : false;
    }

    public function rules(): array
    {
        return [
            [['uuid', 'name', 'moderator_pw', 'attendee_pw', 'creator_user_id'], 'required'],
            [['uuid', 'name', 'title', 'moderator_pw', 'attendee_pw'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['uuid'], 'unique'],
            [['creator_user_id', 'contentcontainer_id', 'created_at', 'updated_at', 'deleted_at', 'ord', 'image_file_id'], 'integer'],
            [['enabled'], 'boolean'],
        ];
    }
    public function getSessionUsers(): ActiveQuery
    {
        return $this->hasMany(SessionUser::class, ['session_id' => 'id']);
    }
    public function getUsers(): ActiveQuery
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])->via('sessionUsers');
    }
    public function getAttendeeUsers(): ActiveQuery
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])
            ->via('sessionUsers', function (ActiveQuery $q) {
                $q->andWhere(['role' => 'attendee']);
            });
    }
    public function getModeratorUsers(): ActiveQuery
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])
            ->via('sessionUsers', function (ActiveQuery $q) {
                $q->andWhere(['role' => 'moderator']);
            });
    }

    public function getImageFile(): ActiveQuery
    {
        return $this->hasOne(File::class, ['id' => 'image_file_id']);
    }
}
