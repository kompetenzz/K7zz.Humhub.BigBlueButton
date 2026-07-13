<?php

namespace k7zz\humhub\bbb\notifications;

use humhub\modules\notification\components\BaseNotification;
use k7zz\humhub\bbb\models\SessionChatReaction;
use Yii;
use yii\helpers\Html;

/**
 * Notifies the author of a chat message when someone reacts to it.
 *
 * originator = user who reacted
 * source     = SessionChatReaction record — HumHub only persists source_class/source_pk,
 *              so emoji and message must be reachable through the source
 */
class ChatMsgReaction extends BaseNotification
{
    public $moduleId = 'bbb';

    protected function category()
    {
        return new ChatReactionCategory();
    }

    public function getUrl(): string
    {
        return $this->source->chat->session->getUrl();
    }

    public function getMailSubject(): string
    {
        return Yii::t('BbbModule.base', '{displayName} reacted to your BBB chat message in: {title}', [
            'displayName' => $this->originator->displayName,
            'title'       => $this->source->chat->session->title,
        ]);
    }

    public function html(): string
    {
        return Yii::t('BbbModule.base', '{displayName} reacted with {emoji} to your message in BBB session "{title}": {message}', [
            'displayName' => Html::tag('strong', Html::encode($this->originator->displayName)),
            'emoji'       => $this->source->emoji,
            'title'       => Html::encode($this->source->chat->session->title),
            'message'     => Html::encode(mb_strimwidth($this->source->chat->message, 0, 80, '…')),
        ]);
    }

    /**
     * Notify the chat message author about a new reaction.
     * Skipped for anonymous authors and self-reactions.
     */
    public static function notifyAuthor(SessionChatReaction $reaction): void
    {
        $chat = $reaction->chat;
        if ($chat === null || $chat->user_id === null || $chat->user_id === $reaction->user_id) {
            return;
        }
        $author = $chat->user;
        if ($author === null || $reaction->user === null) {
            return;
        }

        static::instance()
            ->from($reaction->user)
            ->about($reaction)
            ->send($author);
    }
}
