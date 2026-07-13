<?php

namespace k7zz\humhub\bbb\notifications;

use humhub\modules\notification\components\NotificationCategory;
use humhub\modules\notification\targets\BaseTarget;
use humhub\modules\notification\targets\WebTarget;
use Yii;

/**
 * Notification category for emoji reactions on own BBB chat messages.
 */
class ChatReactionCategory extends NotificationCategory
{
    public $id = 'bbb-chat-reaction';

    public function getTitle(): string
    {
        return Yii::t('BbbModule.base', 'BBB chat reaction received');
    }

    public function getDescription(): string
    {
        return Yii::t('BbbModule.base', 'Receive a notification when someone reacts to one of your BBB chat messages.');
    }

    public function getDefaultSetting(BaseTarget $target): bool
    {
        return $target instanceof WebTarget;
    }
}
