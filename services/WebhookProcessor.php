<?php

namespace k7zz\humhub\bbb\services;

use BigBlueButton\BigBlueButton;
use BigBlueButton\Parameters\SendChatMessageParameters;
use k7zz\humhub\bbb\models\Session;
use k7zz\humhub\bbb\models\SessionMeeting;
use k7zz\humhub\bbb\models\SessionMeetingChat;
use k7zz\humhub\bbb\models\SessionMeetingJoin;
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
            'meeting-started'         => $this->onMeetingStarted($internalId, $externalId),
            'meeting-ended'           => $this->onMeetingEnded($internalId),
            'user-joined'             => $this->onUserJoined($internalId, $attrs['user'] ?? []),
            'user-left'               => $this->onUserLeft($internalId, $attrs['user'] ?? []),
            'chat-group-message-sent' => $this->onChatMessage($internalId, $attrs['chat-message'] ?? []),
            default                   => null,
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

        $meeting = new SessionMeeting([
            'session_id'          => $session->id,
            'internal_meeting_id' => $internalId,
            'started_at'          => time(),
            'created_at'          => time(),
        ]);
        if (!$meeting->save()) {
            Yii::error("BBB webhook: could not save SessionMeeting for {$internalId}", 'bbb');
            return null;
        }

        $this->injectPendingMessages($session, $meeting);
        return $meeting;
    }

    public function onMeetingEnded(string $internalId): bool
    {
        $meeting = SessionMeeting::findByInternalId($internalId);
        if ($meeting === null) {
            return false;
        }
        $meeting->ended_at = time();
        return $meeting->save();
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
        $senderName        = $senderAttrs['name'] ?? '';
        $message           = $msgAttrs['message'] ?? '';

        if ($message === '') {
            return null;
        }

        $join = SessionMeetingJoin::findByInternalUserId($meeting->id, $bbbInternalUserId);

        $chat = new SessionMeetingChat([
            'session_meeting_id' => $meeting->id,
            'session_id'         => $meeting->session_id,
            'user_id'            => $join?->user_id,
            'sender_name'        => $senderName,
            'message'            => $message,
            'source'             => SessionMeetingChat::SOURCE_BBB,
            'sent_at'            => time(),
            'created_at'         => time(),
        ]);
        $chat->save();
        return $chat;
    }

    public function injectPendingMessages(Session $session, SessionMeeting $meeting): void
    {
        $pending = SessionMeetingChat::findPendingForSession($session->id)->all();

        foreach ($pending as $chat) {
            $userName = $chat->sender_name ?: 'HumHub';
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
