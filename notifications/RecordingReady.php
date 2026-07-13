<?php

namespace k7zz\humhub\bbb\notifications;

use humhub\modules\notification\components\BaseNotification;
use humhub\modules\user\models\User;
use k7zz\humhub\bbb\models\Session;
use k7zz\humhub\bbb\models\SessionUser;
use Yii;
use yii\helpers\Html;

/**
 * Notifies session moderators when a BBB recording has been published and is ready to view.
 *
 * originator = session creator (system event, no triggering user)
 * source     = Session model
 */
class RecordingReady extends BaseNotification
{
    public $moduleId = 'bbb';

    // This is a system event, not a social notification: the person who
    // created the session is usually also the moderator who must receive it.
    public $suppressSendToOriginator = false;

    protected function category()
    {
        return new RecordingReadyCategory();
    }

    public function getUrl(): string
    {
        return $this->source->getUrl();
    }

    public function getMailSubject(): string
    {
        return Yii::t('BbbModule.base', 'Recording available for BBB session: {title}', [
            'title' => $this->source->title,
        ]);
    }

    public function html(): string
    {
        return '<i class="fa fa-video-camera"></i> ' . Yii::t('BbbModule.base', 'A recording of BBB session "{title}" is now available.', [
            'title' => Html::encode($this->source->title),
        ]);
    }

    /**
     * Notify all moderators that a recording for this session is ready.
     * Uses the session creator as the display originator (automated event).
     */
    public static function notifyModerators(Session $session): void
    {
        $creator = $session->content->createdBy ?? null;
        if ($creator === null) {
            return;
        }

        $notification = static::instance()->from($creator)->about($session);

        $notifiedIds = [];

        // Explicit moderators
        $moderators = User::find()
            ->innerJoin('bbb_session_user su', 'su.user_id = user.id')
            ->where(['su.session_id' => $session->id, 'su.role' => 'moderator'])
            ->all();
        foreach ($moderators as $user) {
            $notification->send($user);
            $notifiedIds[] = $user->id;
        }

        // Session creator
        if (!in_array($creator->id, $notifiedIds, true)) {
            $notification->send($creator);
            $notifiedIds[] = $creator->id;
        }

        // Profile container owner (User container only)
        $owner = $session->content->container;
        if ($owner instanceof User && !in_array($owner->id, $notifiedIds, true)) {
            $notification->send($owner);
        }
    }
}
