<?php

namespace humhub\modules\bbb\permissions;

use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\modules\admin\components\BaseAdminPermission;

use Yii;

/**
 * Admin permission for the BigBlueButton module.
 *
 * This permission allows users to administer conference sessions, including
 * starting, joining, and managing online meetings.
 */

class Admin extends BaseAdminPermission
{
    protected $moduleId = 'bbb';
    /**
     * @inheritdoc
     */
    public $defaultAllowedGroups = [];

    /**
     * @inheritdoc
     */
    protected $fixedGroups = [
        Space::USERGROUP_USER,
        User::USERGROUP_FRIEND,
        User::USERGROUP_GUEST,
        User::USERGROUP_USER,
        User::USERGROUP_FRIEND,
        Space::USERGROUP_GUEST,
    ];

    public function getTitle()
    {
        return Yii::t('BbbModule.base', 'Administer conference sessions');
    }

    public function getDescription()
    {
        return Yii::t('BbbModule.base', 'Allows the user to maintain all online meetings.');
    }
}
