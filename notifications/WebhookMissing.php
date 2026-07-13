<?php

namespace k7zz\humhub\bbb\notifications;

use humhub\modules\notification\components\BaseNotification;
use humhub\modules\user\models\User;
use k7zz\humhub\bbb\models\Session;
use Yii;
use yii\helpers\Html;

/**
 * Notifies session moderators that BBB webhook events are not being received,
 * which indicates the bbb-webhooks service may not be running or misconfigured.
 */
class WebhookMissing extends BaseNotification
{
    public $moduleId = 'bbb';

    // This is a system warning, not a social notification: the person who
    // started the session is usually also the moderator who must receive it.
    public $suppressSendToOriginator = false;

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
        return Yii::t('BbbModule.base', 'BBB webhook warning for: {title}', [
            'title' => $this->source->title,
        ]);
    }

    public function html(): string
    {
        return '<i class="fa fa-exclamation-triangle"></i> '
            . Yii::t('BbbModule.base', 'No BBB webhook events received for "{title}". The bbb-webhooks service may not be running or is misconfigured.', [
                'title' => Html::encode($this->source->title),
            ]);
    }

    public static function notifyModerators(Session $session): void
    {
        $creator = $session->content->createdBy ?? null;
        if ($creator === null) {
            return;
        }

        $notification = static::instance()->from($creator)->about($session);
        $notifiedIds  = [];

        $moderators = User::find()
            ->innerJoin('bbb_session_user su', 'su.user_id = user.id')
            ->where(['su.session_id' => $session->id, 'su.role' => 'moderator'])
            ->all();
        foreach ($moderators as $user) {
            $notification->send($user);
            $notifiedIds[] = $user->id;
        }

        if (!in_array($creator->id, $notifiedIds, true)) {
            $notification->send($creator);
            $notifiedIds[] = $creator->id;
        }

        $owner = $session->content->container;
        if ($owner instanceof User && !in_array($owner->id, $notifiedIds, true)) {
            $notification->send($owner);
        }
    }
}
