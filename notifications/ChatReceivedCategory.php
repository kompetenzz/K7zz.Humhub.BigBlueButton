<?php

namespace k7zz\humhub\bbb\notifications;

use humhub\modules\notification\components\NotificationCategory;
use humhub\modules\notification\targets\BaseTarget;
use humhub\modules\notification\targets\WebTarget;
use Yii;

class ChatReceivedCategory extends NotificationCategory
{
    public $id = 'bbb-chat-received';

    public function getTitle()
    {
        return Yii::t('BbbModule.base', 'BBB chat message received');
    }

    public function getDescription()
    {
        return Yii::t('BbbModule.base', 'Receive a notification when someone writes in a chat of a BBB session moderated by you.');
    }

    public function getDefaultSetting(BaseTarget $target): bool
    {
        return $target instanceof WebTarget;
    }
}
