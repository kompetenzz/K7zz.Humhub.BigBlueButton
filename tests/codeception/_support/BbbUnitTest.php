<?php

namespace bbb;

use humhub\libs\UUID;
use k7zz\humhub\bbb\models\Session;
use k7zz\humhub\bbb\models\SessionMeeting;
use k7zz\humhub\bbb\models\SessionMeetingChat;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

/**
 * Base class for BBB unit tests.
 *
 * WebhookProcessor tests do not need a fully-fledged ContentActiveRecord session.
 * Session::findOne(['uuid' => ...]) does a simple SELECT on bbb_session (no join),
 * so a raw DB insert is sufficient and avoids fixture/content-container complexity.
 */
class BbbUnitTest extends HumHubDbTestCase
{
    public function _before()
    {
        parent::_before();

        $settings = Yii::$app->getModule('bbb')->settings;
        $settings->set('bbbUrl', 'https://bbb.example.test/');
        $settings->set('bbbSecret', 'test-secret');
        $settings->set('integrateBbbChat', true);
    }

    /**
     * Inserts a minimal bbb_session row directly (bypasses ContentActiveRecord).
     * The creator_user_id 1 is the Admin user from the default fixtures.
     */
    protected function createSession(
        string $name = 'test-session',
        string $title = 'Test Session',
        array $attributes = []
    ): Session {
        $now  = time();
        $uuid = $attributes['uuid'] ?? UUID::v4();

        Yii::$app->db->createCommand()->insert('bbb_session', [
            'uuid'                => $uuid,
            'name'                => $name,
            'title'               => $title,
            'moderator_pw'        => 'mod-pw',
            'attendee_pw'         => 'att-pw',
            'creator_user_id'     => 1, // Admin fixture user
            'created_at'          => $now,
            'updated_at'          => $now,
            'enabled'             => 1,
            'layout'              => 'SMART_LAYOUT',
            'integrate_bbb_chat'  => (int) ($attributes['integrate_bbb_chat'] ?? true),
        ])->execute();

        $session = Session::findOne(['uuid' => $uuid]);
        $this->assertNotNull($session, 'Could not find Session after insert');
        return $session;
    }

    protected function createMeeting(Session $session, string $internalId): SessionMeeting
    {
        $meeting = new SessionMeeting([
            'session_id'          => $session->id,
            'internal_meeting_id' => $internalId,
            'started_at'          => time(),
            'created_at'          => time(),
        ]);
        $this->assertTrue($meeting->save(), 'Could not save SessionMeeting: ' . print_r($meeting->getErrors(), true));
        return $meeting;
    }

    protected function queueMessage(SessionMeeting $meeting, string $text, string $senderName = 'Test User'): SessionMeetingChat
    {
        $chat = new SessionMeetingChat([
            'session_meeting_id' => $meeting->id,
            'sender_name'        => $senderName,
            'message'            => $text,
            'source'             => SessionMeetingChat::SOURCE_HUMHUB,
            'sent_at'            => null,
            'created_at'         => time(),
        ]);
        $this->assertTrue($chat->save(), 'Could not save SessionMeetingChat: ' . print_r($chat->getErrors(), true));
        return $chat;
    }

    protected function buildEvent(string $type, string $internalId, string $externalId, array $extra = []): array
    {
        return [
            'data' => [
                'id'         => $type,
                'attributes' => array_merge([
                    'meeting' => [
                        'internal-meeting-id' => $internalId,
                        'external-meeting-id' => $externalId,
                    ],
                ], $extra),
            ],
        ];
    }
}
