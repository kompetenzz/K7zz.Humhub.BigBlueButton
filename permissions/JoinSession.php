<?php

namespace k7zz\humhub\bbb\permissions;

use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Yii;

/**
 * Permission to join a BigBlueButton session.
 *
 * Allows users to join online conference sessions if they belong to allowed groups.
 */
class JoinSession extends \humhub\libs\BasePermission
{
    /** @var string The module ID for this permission. */
    protected $moduleId = 'bbb';
    /** @var string The default state for this permission. */
    protected $defaultState = self::STATE_ALLOW;

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return Yii::t('BbbModule.base', 'Join online conference session');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return Yii::t('BbbModule.base', 'Allows the user to join a session.');
    }
}
