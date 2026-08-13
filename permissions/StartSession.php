<?php

namespace k7zz\humhub\bbb\permissions;

use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Yii;

/**
 * Permission to start a BigBlueButton session.
 *
 * Allows users to start online conference sessions if they belong to allowed groups.
 */
class StartSession extends BBBPermission
{
    /** @inheritdoc */
    public $defaultAllowedGroups = [
        Space::USERGROUP_ADMIN,
        Space::USERGROUP_MODERATOR,
        User::USERGROUP_SELF,
    ];

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return Yii::t('BbbModule.base', 'Start online conference session');
    }
    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return Yii::t('BbbModule.base', 'Allows the user to start a session.');
    }
}
