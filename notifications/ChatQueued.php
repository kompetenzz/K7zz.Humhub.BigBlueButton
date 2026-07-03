<?php

namespace k7zz\humhub\bbb\notifications;

use humhub\modules\notification\components\BaseNotification;
use humhub\modules\user\models\User;
use Yii;
use yii\helpers\Html;

/**
 * Notifies moderators when a participant queues a pre-meeting chat message.
 *
 * originator = user who sent the message
 * source     = Session model
 */
class ChatQueued extends BaseNotification
{
    public $moduleId = 'bbb';

    /** The queued message text (set before calling send/sendBulk) */
    public string $messageText = '';

    protected function category()
    {
        $isUserContainer = $this->source
            && $this->source->content
            && $this->source->content->container instanceof User;
        return new SessionStartedCategory(['isUserContainer' => $isUserContainer]);
    }

    public function getUrl(): string
    {
        return $this->source->getUrl();
    }

    public function getMailSubject(): string
    {
        return Yii::t('BbbModule.base', '{displayName} sent a pre-meeting message for: {title}', [
            'displayName' => $this->originator->displayName,
            'title'       => $this->source->title,
        ]);
    }

    public function html(): string
    {
        return '<i class="fa fa-comment"></i> ' . Yii::t('BbbModule.base', '{displayName} wrote before the meeting "{title}": {message}', [
            'displayName' => Html::tag('strong', Html::encode($this->originator->displayName)),
            'title'       => Html::encode($this->source->title),
            'message'     => Html::encode(mb_strimwidth($this->messageText, 0, 80, '…')),
        ]);
    }
}
