<?php

namespace k7zz\humhub\bbb\notifications;

use humhub\modules\notification\components\NotificationCategory;
use humhub\modules\notification\targets\BaseTarget;
use Yii;

class SessionStartedCategory extends NotificationCategory
{
    public $id = 'bbb-session-started';

    /** @var bool True when the session lives on a User profile (default ON), false for Spaces (default OFF) */
    public bool $isUserContainer = false;

    public function getTitle()
    {
        return Yii::t('BbbModule.base', 'BBB session started');
    }

    public function getDescription()
    {
        return Yii::t('BbbModule.base', 'Receive a notification when a BBB session you are invited to has started.');
    }

    public function getDefaultSetting(BaseTarget $target): bool
    {
        return $this->isUserContainer;
    }
}
