<?php

namespace k7zz\humhub\bbb\models;

use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use humhub\libs\Html;
use humhub\modules\user\models\User;
use Yii;

/**
 * One chat message, either off-meeting (session_meeting_id = null) or in-meeting.
 *
 * Off-meeting (queued by HumHub user before the meeting starts):
 *   session_id = X, session_meeting_id = null, source = 'humhub', sent_at = null
 *
 * In-meeting from HumHub (injected into BBB on meeting start):
 *   session_id = X, session_meeting_id = M, source = 'humhub', sent_at = timestamp
 *
 * From BBB via webhook:
 *   session_meeting_id = M, source = 'bbb', sent_at = timestamp
 *
 * @property int         $id
 * @property int|null    $session_id          set for off-meeting messages
 * @property int|null    $session_meeting_id
 * @property int|null    $user_id             HumHub user (null for external/anonymous)
 * @property string      $sender_name
 * @property string      $message
 * @property string      $source              humhub|bbb|system
 * @property int|null    $sent_at             null = pending injection
 * @property int|null    $edited_at           null = never edited
 * @property int         $created_at
 */
class SessionMeetingChat extends ActiveRecord
{
    public const SOURCE_HUMHUB = 'humhub';
    public const SOURCE_BBB    = 'bbb';
    public const SOURCE_SYSTEM = 'system';
    public const BBB_MSG_SUFFIX = ' [ext]';

    public static function tableName(): string
    {
        return 'bbb_session_meeting_chat';
    }

    public function rules(): array
    {
        return [
            [['message', 'source'], 'required'],
            [['session_id', 'session_meeting_id', 'user_id', 'sent_at', 'edited_at', 'created_at'], 'integer'],
            [['sender_name'], 'string', 'max' => 255],
            [['source'], 'in', 'range' => [self::SOURCE_HUMHUB, self::SOURCE_BBB, self::SOURCE_SYSTEM]],
            [['message'], 'string'],
        ];
    }

    public function beforeSave($insert): bool
    {
        if ($insert && !$this->created_at) {
            $this->created_at = time();
        }
        return parent::beforeSave($insert);
    }

    /**
     * Message rendered as safe HTML for the chat bubble: input is fully
     * encoded first, then URLs are auto-linked and WhatsApp-style inline
     * markup is applied — *bold*, _italic_, ~strike~, `code`.
     */
    public function getFormattedMessage(): string
    {
        $text = Html::encode(trim($this->message));

        // Extract URLs first so inline markup can't mangle them (e.g. underscores in paths)
        $links = [];
        $text = preg_replace_callback('~https?://(?:(?!&lt;|&gt;|&quot;|&#039;)\S)+~i', function ($m) use (&$links) {
            $url   = $m[0];
            $trail = '';
            // Trailing sentence punctuation is almost never part of the URL
            if (preg_match('~[.,;:!?)]+$~', $url, $t)) {
                $trail = $t[0];
                $url   = substr($url, 0, -strlen($trail));
            }
            $key = "\x1A" . count($links) . "\x1A";
            $links[$key] = '<a href="' . $url . '" target="_blank" rel="noopener nofollow">' . $url . '</a>';
            return $key . $trail;
        }, $text);

        $text = preg_replace('~\*(?!\s)([^*\n]+?)(?<!\s)\*~u', '<strong>$1</strong>', $text);
        $text = preg_replace('~(?<![\w])_(?!\s)([^_\n]+?)(?<!\s)_(?![\w])~u', '<em>$1</em>', $text);
        $text = preg_replace('/~(?!\s)([^~\n]+?)(?<!\s)~/u', '<del>$1</del>', $text);
        $text = preg_replace('~`([^`\n]+)`~u', '<code>$1</code>', $text);

        return nl2br(strtr($text, $links));
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getMeeting(): ActiveQuery
    {
        return $this->hasOne(SessionMeeting::class, ['id' => 'session_meeting_id']);
    }

    public function getSession(): ActiveQuery
    {
        return $this->hasOne(Session::class, ['id' => 'session_id']);
    }

    /**
     * Returns all pending HumHub messages for a session (not yet assigned to a meeting).
     */
    public static function findPendingForSession(int $sessionId): ActiveQuery
    {
        return static::find()->where([
            'session_id'          => $sessionId,
            'session_meeting_id'  => null,
            'source'              => self::SOURCE_HUMHUB,
            'sent_at'             => null,
        ])->orderBy(['created_at' => SORT_ASC]);
    }

    /**
     * Returns all chat messages for a session that are visible in the off-meeting view.
     * Shows queued (pending) HumHub messages only.
     */
    public static function findPreMeetingForSession(int $sessionId): ActiveQuery
    {
        return static::find()
            ->where(['session_id' => $sessionId, 'session_meeting_id' => null])
            ->orderBy(['created_at' => SORT_ASC]);
    }

    /**
     * Returns all messages for a session across all meetings (off-meeting + in-meeting + BBB-received).
     */
    public static function findAllForSession(int $sessionId, int $limit = 300): ActiveQuery
    {
        return static::find()
            ->where(['session_id' => $sessionId])
            ->with(['user'])
            ->orderBy(['created_at' => SORT_ASC, 'id' => SORT_ASC])
            ->limit($limit);
    }
}
