<?php

namespace k7zz\humhub\bbb\models;

use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use humhub\modules\user\models\User;

/**
 * One emoji reaction of one user on one chat message.
 *
 * @property int    $id
 * @property int    $chat_id
 * @property int    $user_id
 * @property string $emoji
 * @property int    $created_at
 */
class SessionChatReaction extends ActiveRecord
{
    /** Emojis offered by the picker; also the server-side whitelist. */
    public const ALLOWED_EMOJIS = ['👍', '❤️', '😂', '😮', '🎉'];

    public static function tableName(): string
    {
        return 'bbb_session_chat_reaction';
    }

    public function rules(): array
    {
        return [
            [['chat_id', 'user_id', 'emoji'], 'required'],
            [['chat_id', 'user_id', 'created_at'], 'integer'],
            [['emoji'], 'in', 'range' => self::ALLOWED_EMOJIS],
        ];
    }

    public function beforeSave($insert): bool
    {
        if ($insert && !$this->created_at) {
            $this->created_at = time();
        }
        return parent::beforeSave($insert);
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getChat(): ActiveQuery
    {
        return $this->hasOne(SessionMeetingChat::class, ['id' => 'chat_id']);
    }

    /**
     * Toggles a reaction: creates it, or removes it if it already exists.
     * Returns the reaction record when created, null when removed.
     */
    public static function toggle(int $chatId, int $userId, string $emoji): ?self
    {
        $existing = static::findOne(['chat_id' => $chatId, 'user_id' => $userId, 'emoji' => $emoji]);
        if ($existing !== null) {
            $existing->delete();
            return null;
        }

        $reaction = new static([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'emoji'   => $emoji,
        ]);
        return $reaction->save() ? $reaction : null;
    }

    /**
     * Returns reactions for many chat messages at once, grouped by chat_id:
     * [chatId => [emoji => ['count' => n, 'own' => bool, 'names' => string[]]]]
     */
    public static function findGroupedForChats(array $chatIds, ?int $currentUserId): array
    {
        if (empty($chatIds)) {
            return [];
        }

        $grouped = [];
        $reactions = static::find()
            ->where(['chat_id' => $chatIds])
            ->with(['user'])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();

        foreach ($reactions as $r) {
            $entry = &$grouped[$r->chat_id][$r->emoji];
            $entry['count'] = ($entry['count'] ?? 0) + 1;
            $entry['own']   = ($entry['own'] ?? false) || ($currentUserId !== null && $r->user_id === $currentUserId);
            $entry['names'][] = $r->user->displayName ?? '?';
            unset($entry);
        }

        return $grouped;
    }
}
