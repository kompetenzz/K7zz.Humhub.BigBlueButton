<?php

namespace k7zz\humhub\bbb\permissions;

use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Yii;

class JoinSession extends \humhub\libs\BasePermission
{
    protected $moduleId = 'bbb';
    protected $defaultState = self::STATE_ALLOW;

    public function getTitle()
    {
        return Yii::t('BbbModule.base', 'Join online conference session');
    }

    public function getDescription()
    {
        return Yii::t('BbbModule.base', 'Allows the user to join a session.');
    }
}
