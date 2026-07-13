<?php

namespace k7zz\humhub\bbb\services;

use BigBlueButton\BigBlueButton;
use BigBlueButton\Parameters\SendChatMessageParameters;
use humhub\modules\user\models\User;
use k7zz\humhub\bbb\models\Session;
use k7zz\humhub\bbb\models\SessionMeeting;
use k7zz\humhub\bbb\models\SessionMeetingChat;
use k7zz\humhub\bbb\models\SessionMeetingJoin;
use k7zz\humhub\bbb\notifications\ChatMsgReceived;
use k7zz\humhub\bbb\notifications\RecordingReady;
use Yii;

/**
 * Processes incoming BBB webhook events and persists them to the database.
 *
 * Extracted from WebhookController so it can be unit-tested independently.
 */
class WebhookProcessor
{
    private BigBlueButton $bbb;

    public function __construct()
    {
        $settings = Yii::$app->getModule('bbb')->settings;
        $baseUrl  = rtrim($settings->get('bbbUrl') ?? '', '/') . '/';
        $secret   = $settings->get('bbbSecret') ?? '';
        $this->bbb = new BigBlueButton($baseUrl, $secret);
    }

    /**
     * Dispatches a single BBB event array (one element of the outer events array).
     */
    public function process(array $event): void
    {
        $type = $event['data']['id'] ?? null;
        if ($type === null) {
            return;
        }

        $attrs        = $event['data']['attributes'] ?? [];
        $meetingAttrs = $attrs['meeting'] ?? [];
        $internalId   = $meetingAttrs['internal-meeting-id'] ?? null;
        $externalId   = $meetingAttrs['external-meeting-id'] ?? null;

        if ($internalId === null) {
            return;
        }

        match ($type) {
            'meeting-created',
            'meeting-started'              => $this->onMeetingStarted($internalId, $externalId),
            'meeting-ended'                => $this->onMeetingEnded($internalId),
            'user-joined'                  => $this->onUserJoined($internalId, $attrs['user'] ?? []),
            'user-left'                    => $this->onUserLeft($internalId, $attrs['user'] ?? []),
            'chat-group-message-sent'      => $this->onChatMessage($internalId, $attrs['chat-message'] ?? []),
            'meeting-recording-started',
            'meeting-recording-stopped'    => $this->onRecordingStateChanged($internalId, $type),
            'rap-publish-ended'            => $this->onRecordingPublished($internalId, $externalId),
            default                        => null,
        };
    }

    public function onMeetingStarted(string $internalId, ?string $externalId): ?SessionMeeting
    {
        if (SessionMeeting::findByInternalId($internalId) !== null) {
            return null; // idempotent
        }

        $session = $externalId ? Session::findOne(['uuid' => $externalId]) : null;
        if ($session === null) {
            Yii::warning("BBB webhook meeting-started: no session for uuid={$externalId}", 'bbb');
            return null;
        }

        $now     = time();
        $meeting = new SessionMeeting([
            'session_id'          => $session->id,
            'internal_meeting_id' => $internalId,
            'started_at'          => $now,
            'created_at'          => $now,
        ]);
        if (!$meeting->save()) {
            Yii::error("BBB webhook: could not save SessionMeeting for {$internalId}", 'bbb');
            return null;
        }

        (new SessionMeetingChat([
            'session_id'         => $session->id,
            'session_meeting_id' => $meeting->id,
            'source'             => SessionMeetingChat::SOURCE_SYSTEM,
            'message'            => 'meeting-started',
            'sender_name'        => '',
            'created_at'         => $now,
        ]))->save();

        // Webhook did arrive — clear a possible false-positive hook-failed banner flag
        Yii::$app->cache->delete('bbb:hook_failed:' . $session->id);

        return $meeting;
    }

    public function onMeetingEnded(string $internalId): bool
    {
        $meeting = SessionMeeting::findByInternalId($internalId);
        if ($meeting === null) {
            return false;
        }
        $now             = time();
        $meeting->ended_at = $now;
        if (!$meeting->save()) {
            return false;
        }

        (new SessionMeetingChat([
            'session_id'         => $meeting->session_id,
            'session_meeting_id' => null,
            'source'             => SessionMeetingChat::SOURCE_SYSTEM,
            'message'            => 'meeting-ended',
            'sender_name'        => '',
            'created_at'         => $now,
        ]))->save();

        return true;
    }

    public function onUserJoined(string $internalId, array $userAttrs): ?SessionMeetingJoin
    {
        $meeting = SessionMeeting::findByInternalId($internalId);
        if ($meeting === null) {
            return null;
        }

        $bbbInternalUserId = $userAttrs['internal-user-id'] ?? '';
        $externalUserId    = $userAttrs['external-user-id'] ?? null;
        $displayName       = $userAttrs['name'] ?? '';
        $role              = strtolower($userAttrs['role'] ?? 'viewer');

        $humhubUserId = ($externalUserId !== null && ctype_digit((string) $externalUserId))
            ? (int) $externalUserId
            : null;

        if (SessionMeetingJoin::findByInternalUserId($meeting->id, $bbbInternalUserId) !== null) {
            return null; // idempotent
        }

        $join = new SessionMeetingJoin([
            'session_meeting_id'   => $meeting->id,
            'user_id'              => $humhubUserId,
            'bbb_internal_user_id' => $bbbInternalUserId,
            'display_name'         => $displayName,
            'role'                 => $role,
            'joined_at'            => time(),
        ]);
        $join->save();
        return $join;
    }

    public function onUserLeft(string $internalId, array $userAttrs): bool
    {
        $meeting = SessionMeeting::findByInternalId($internalId);
        if ($meeting === null) {
            return false;
        }

        $bbbInternalUserId = $userAttrs['internal-user-id'] ?? '';
        $join = SessionMeetingJoin::findByInternalUserId($meeting->id, $bbbInternalUserId);
        if ($join === null) {
            return false;
        }
        $join->left_at = time();
        return $join->save();
    }

    public function onChatMessage(string $internalId, array $msgAttrs): ?SessionMeetingChat
    {
        $meeting = SessionMeeting::findByInternalId($internalId);
        if ($meeting === null) {
            return null;
        }

        $senderAttrs       = $msgAttrs['sender'] ?? [];
        $bbbInternalUserId = $senderAttrs['internal-user-id'] ?? '';
        $externalUserId    = $senderAttrs['external-user-id'] ?? null;
        $senderName        = trim($senderAttrs['name'] ?? '');
        $message           = trim($msgAttrs['message'] ?? '');

        if ($message === '') {
            return null;
        }

        // Primary: external-user-id from BBB event = HumHub user ID
        $userId = ($externalUserId !== null && ctype_digit((string) $externalUserId))
            ? (int) $externalUserId
            : null;

        // Secondary: look up via SessionMeetingJoin (populated by user-joined events)
        if ($userId === null) {
            $join   = SessionMeetingJoin::findByInternalUserId($meeting->id, $bbbInternalUserId);
            $userId = $join?->user_id;
        }

        // Fallback: match by sender name within session's HumHub messages
        if ($userId === null && $senderName !== '') {
            $ref = SessionMeetingChat::find()
                ->select('user_id')
                ->where([
                    'session_id'  => $meeting->session_id,
                    'source'      => SessionMeetingChat::SOURCE_HUMHUB,
                    'sender_name' => $senderName,
                ])
                ->andWhere(['not', ['user_id' => null]])
                ->scalar();
            $userId = $ref ? (int) $ref : null;
        }

        // Skip echo: BBB reflects injected HumHub messages back with the BBB_MSG_SUFFIX on the sender name.
        $unprefixedSender = str_ends_with($senderName, SessionMeetingChat::BBB_MSG_SUFFIX)
            ? substr($senderName, 0, -strlen(SessionMeetingChat::BBB_MSG_SUFFIX))
            : $senderName;

        $isEcho = SessionMeetingChat::find()->where([
            'session_meeting_id' => $meeting->id,
            'source'             => SessionMeetingChat::SOURCE_HUMHUB,
            'sender_name'        => $unprefixedSender,
            'message'            => $message,
        ])->andWhere(['not', ['sent_at' => null]])->exists();
        if ($isEcho) {
            return null;
        }

        $chat = new SessionMeetingChat([
            'session_meeting_id' => $meeting->id,
            'session_id'         => $meeting->session_id,
            'user_id'            => $userId,
            'sender_name'        => $senderName,
            'message'            => $message,
            'source'             => SessionMeetingChat::SOURCE_BBB,
            'sent_at'            => time(),
            'created_at'         => time(),
        ]);
        $chat->save();

        $originator = $userId ? User::findOne($userId) : null;
        ChatMsgReceived::notifyModerators($chat, $originator);

        return $chat;
    }

    public function onRecordingStateChanged(string $internalId, string $type): void
    {
        $meeting = SessionMeeting::findByInternalId($internalId);
        if ($meeting === null) {
            return;
        }

        $message = ($type === 'meeting-recording-started') ? 'recording-started' : 'recording-stopped';
        (new SessionMeetingChat([
            'session_id'         => $meeting->session_id,
            'session_meeting_id' => $meeting->id,
            'source'             => SessionMeetingChat::SOURCE_SYSTEM,
            'message'            => $message,
            'sender_name'        => '',
            'created_at'         => time(),
        ]))->save();
    }

    public function onRecordingPublished(string $internalId, ?string $externalId): void
    {
        $session = $externalId ? Session::findOne(['uuid' => $externalId]) : null;
        if ($session === null) {
            $meeting = SessionMeeting::findByInternalId($internalId);
            if ($meeting === null) {
                return;
            }
            $session = Session::findOne($meeting->session_id);
        }
        if ($session === null) {
            return;
        }

        RecordingReady::notifyModerators($session);
    }

    public function injectPendingMessages(Session $session, SessionMeeting $meeting): void
    {
        $pending = SessionMeetingChat::findPendingForSession($session->id)->all();

        foreach ($pending as $chat) {
            $userName = ($chat->sender_name . SessionMeetingChat::BBB_MSG_SUFFIX) ?: 'von extern';
            $params   = new SendChatMessageParameters($session->uuid, $chat->message, $userName);
            $result   = $this->bbb->getSendChatMessage($params);
            if ($result->success()) {
                $chat->session_meeting_id = $meeting->id;
                $chat->sent_at            = time();
                $chat->save();
            } else {
                Yii::error("BBB inject message failed for session {$session->name}: " . $result->getMessage(), 'bbb');
            }
        }
    }
}
