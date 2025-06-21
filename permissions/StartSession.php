<?php

namespace k7zz\humhub\bbb\permissions;

use humhub\modules\space\models\Space;
use humhub\modules\user\models\Group;
use humhub\modules\user\models\User;
use Yii;

class StartSession extends \humhub\libs\BasePermission
{
    protected $moduleId = 'bbb';
    /**
     * @inheritdoc
     */
    public $defaultAllowedGroups = [
        Space::USERGROUP_ADMIN,
        Space::USERGROUP_MODERATOR,
        User::USERGROUP_SELF,
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->defaultAllowedGroups[] = Group::getAdminGroupId();

        parent::init();
    }

    public function getTitle()
    {
        return Yii::t('BbbModule.base', 'Start online conference session');
    }

    public function getDescription()
    {
        return Yii::t('BbbModule.base', 'Allows the user to start a session.');
    }
}
