<?php
namespace k7zz\humhub\bbb\models;
use Yii;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\libs\BasePermission;
use yii\db\ActiveQuery;
use humhub\modules\file\converter\PreviewImage;
use humhub\modules\file\models\File;
use humhub\modules\user\components\User as UserComponent;
use humhub\modules\user\models\User;
use k7zz\humhub\bbb\permissions\{
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
class Session extends ContentActiveRecord
{
    protected $moduleId = 'bbb';
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
    public $wallEntryClass = null; // optional, falls keine Wall-Darstellung
    public $autoAddToWall = true; // optional

    public function getContentName()
    {
        return $this->title ?: Yii::t('BbbModule.base', 'A live session');
    }

    public function getContentDescription()
    {
        return $this->description ?: Yii::t('BbbModule.base', 'Live video session with BigBlueButton');
    }

    public function canAdminister(?UserComponent $user = null): bool
    {
        $user ??= Yii::$app->user;

        if ($this->content->canEdit($user->identity)) {
            return true; //  globale bzw. Container-Permission
        }

        return $this->can($user, Admin::class);
    }

    /** darf $user diese Session starten? */
    public function canStart(?UserComponent $user = null): bool
    {
        $user ??= Yii::$app->user;

        if ($this->canAdminister($user)) {
            return true; //  globale bzw. Container-Permission
        }

        if ($this->join_can_start && $this->hasJoinPermission($user)) {
            return true; //  dürfen immer starten, wenn sie beitreten können
        }

        return $this->hasStartPermission($user);
    }

    private function hasStartPermission(?UserComponent $user = null): bool
    {
        $user ??= Yii::$app->user;

        if ($this->can($user, StartSession::class)) {
            return true; //  globale bzw. Container-Permission
        }

        $pivot = SessionUser::findOne(['session_id' => $this->id, 'user_id' => $user->id]);
        return $pivot ? (bool) $pivot->can_start : false;
    }

    /** darf $user beitreten? */
    public function canJoin(?UserComponent $user = null): bool
    {
        $user ??= Yii::$app->user;

        if ($this->canStart($user)) {
            return true; //  dürfen immer beitreten
        }

        return $this->hasJoinPermission($user);
    }

    private function hasJoinPermission(?UserComponent $user = null): bool
    {
        $user ??= Yii::$app->user;

        if ($this->can($user, JoinSession::class)) {
            return true; //  globale bzw. Container-Permission
        }

        $pivot = SessionUser::findOne(['session_id' => $this->id, 'user_id' => $user->id]);
        return $pivot ? (bool) $pivot->can_join : false;
    }


    public function isModerator(?UserComponent $user = null): bool
    {
        $user ??= Yii::$app->user;
        if ($this->can($user, Admin::class)) {
            return true; //  globale bzw. Container-Permission
        }
        if ($this->join_can_moderate && $this->canJoin($user)) {
            return true; //  dürfen immer starten, wenn sie beitreten können
        }

        $pivot = SessionUser::findOne(['session_id' => $this->id, 'user_id' => $user->id]);
        return $pivot ? (bool) $pivot->role === 'moderator' || $this->join_can_moderate : false;
    }

    private function can(?UserComponent $user, BasePermission|string $permission): bool
    {
        $user ??= Yii::$app->user;

        // Kein Container → prüfe globale Berechtigung
        if (!$this->content || !$this->content->container) {
            return $user->can($permission);
        }
        $container = $this->content->container;
        if ($container instanceof ContentContainerActiveRecord && $container->can($permission, ['user' => $user])) {
            return true;
        }

        return false;
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
