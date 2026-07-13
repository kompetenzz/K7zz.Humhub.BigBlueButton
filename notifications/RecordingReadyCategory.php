<?php

namespace k7zz\humhub\bbb\notifications;

use humhub\modules\notification\components\NotificationCategory;
use humhub\modules\notification\targets\BaseTarget;
use humhub\modules\notification\targets\MailTarget;
use humhub\modules\notification\targets\WebTarget;
use Yii;

class RecordingReadyCategory extends NotificationCategory
{
    public $id = 'bbb-recording-ready';

    public function getTitle()
    {
        return Yii::t('BbbModule.base', 'BBB recording available');
    }

    public function getDescription()
    {
        return Yii::t('BbbModule.base', 'Receive a notification when a BBB session recording is available.');
    }

    public function getDefaultSetting(BaseTarget $target): bool
    {
        return $target instanceof WebTarget || $target instanceof MailTarget;
    }
}
