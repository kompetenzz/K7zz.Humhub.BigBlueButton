<?php

namespace k7zz\humhub\bbb\permissions;

use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Yii;

/**
 * Manage permission for BigBlueButton sessions.
 *
 * Allows users to administer conference sessions, including starting, joining, and managing online meetings.
 */
class ManageSession extends BBBPermission
{    /** @inheritdoc */
    public $defaultAllowedGroups = [
        Space::USERGROUP_ADMIN,
        Space::USERGROUP_OWNER,
        User::USERGROUP_SELF,
    ];
    /** @inheritdoc */
    protected $fixedGroups = [
        Space::USERGROUP_USER,
        User::USERGROUP_FRIEND,
        User::USERGROUP_GUEST,
        User::USERGROUP_USER,
        User::USERGROUP_FRIEND,
        Space::USERGROUP_GUEST,
    ];

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return Yii::t('BbbModule.base', 'Administer conference sessions');
    }
    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return Yii::t('BbbModule.base', 'Allows the user to maintain all sessions.');
    }
}
