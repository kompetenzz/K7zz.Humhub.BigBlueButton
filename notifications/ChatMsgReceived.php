<?php

namespace k7zz\humhub\bbb\notifications;

use humhub\modules\notification\components\BaseNotification;
use humhub\modules\user\models\User;
use k7zz\humhub\bbb\models\SessionMeetingChat;
use k7zz\humhub\bbb\models\SessionUser;
use Yii;
use yii\helpers\Html;

/**
 * Notifies session moderators when a chat message is received (off-meeting or live).
 *
 * originator = user who sent the message (null = anonymous BBB participant → no notification)
 * source     = SessionMeetingChat record — HumHub only persists source_class/source_pk,
 *              so the message text must live in the source, not in a runtime property
 */
class ChatMsgReceived extends BaseNotification
{
    public $moduleId = 'bbb';

    protected function category()
    {
        return new ChatReceivedCategory();
    }

    public function getUrl(): string
    {
        return $this->source->session->getUrl();
    }

    public function getMailSubject(): string
    {
        return Yii::t('BbbModule.base', '{displayName} sent a BBB chat message in: {title}', [
            'displayName' => $this->originator->displayName,
            'title'       => $this->source->session->title,
        ]);
    }

    public function html(): string
    {
        return '<i class="fa fa-comment"></i> ' . Yii::t('BbbModule.base', '{displayName} wrote in BBB session "{title}": {message}', [
            'displayName' => Html::tag('strong', Html::encode($this->originator->displayName)),
            'title'       => Html::encode($this->source->session->title),
            'message'     => Html::encode(mb_strimwidth($this->source->message, 0, 80, '…')),
        ]);
    }

    /**
     * Notify all moderators of the chat's session about a new chat message.
     *
     * Covers: explicit bbb_session_user moderators, session creator, profile container owner.
     * No notification is sent if $originator is null (anonymous BBB participant).
     */
    public static function notifyModerators(SessionMeetingChat $chat, ?User $originator): void
    {
        if ($originator === null) {
            return;
        }
        $session = $chat->session;
        if ($session === null) {
            return;
        }

        $notification = static::instance()
            ->from($originator)
            ->about($chat);

        // Explicit moderators listed in bbb_session_user
        $moderatorQuery = User::find()
            ->innerJoin('bbb_session_user su', 'su.user_id = user.id')
            ->where(['su.session_id' => $session->id, 'su.role' => 'moderator']);
        $notification->sendBulk($moderatorQuery);

        $alreadyNotifiedIds = array_column(
            SessionUser::find()
                ->select('user_id')
                ->where(['session_id' => $session->id, 'role' => 'moderator'])
                ->asArray()->all(),
            'user_id'
        );

        // Session creator (content author)
        $creator = $session->content->createdBy ?? null;
        if ($creator && $creator->id !== $originator->id && !in_array($creator->id, $alreadyNotifiedIds, true)) {
            $notification->send($creator);
            $alreadyNotifiedIds[] = $creator->id;
        }

        // Profile container owner (User container only)
        $owner = $session->content->container;
        if ($owner instanceof User
            && $owner->id !== $originator->id
            && !in_array($owner->id, $alreadyNotifiedIds, true)
        ) {
            $notification->send($owner);
        }
    }
}
