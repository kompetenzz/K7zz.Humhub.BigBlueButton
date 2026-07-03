<?php

namespace k7zz\humhub\bbb\tests\codeception\unit\services;

use bbb\BbbUnitTest;
use k7zz\humhub\bbb\models\SessionMeeting;
use k7zz\humhub\bbb\models\SessionMeetingChat;
use k7zz\humhub\bbb\models\SessionMeetingJoin;
use k7zz\humhub\bbb\services\WebhookProcessor;

/**
 * Tests the WebhookProcessor event-handling methods in isolation.
 *
 * Uses the test DB — no real BBB server needed.
 * The injectPendingMessages path is NOT tested here (requires a live BBB API);
 * it is covered by the manual meeting test.
 */
class WebhookProcessorTest extends BbbUnitTest
{
    private function makeProcessor(): WebhookProcessor
    {
        return new WebhookProcessor();
    }

    // ──────────────────────────── meeting-started ────────────────────────────

    public function testMeetingStartedCreatesMeetingRecord()
    {
        $session = $this->createSession('ws-started', 'WS Started');
        $p = $this->makeProcessor();

        $meeting = $p->onMeetingStarted('int-id-001', $session->uuid);

        $this->assertNotNull($meeting);
        $this->assertEquals($session->id, $meeting->session_id);
        $this->assertEquals('int-id-001', $meeting->internal_meeting_id);
        $this->assertNull($meeting->ended_at);
    }

    public function testMeetingStartedIsIdempotent()
    {
        $session = $this->createSession('ws-idempotent', 'WS Idempotent');
        $p = $this->makeProcessor();

        $first  = $p->onMeetingStarted('int-id-002', $session->uuid);
        $second = $p->onMeetingStarted('int-id-002', $session->uuid);

        $this->assertNotNull($first);
        $this->assertNull($second); // second call returns null (already exists)
        $this->assertEquals(1, SessionMeeting::find()->where(['internal_meeting_id' => 'int-id-002'])->count());
    }

    public function testMeetingStartedWithUnknownUuidReturnsNull()
    {
        $p = $this->makeProcessor();
        $result = $p->onMeetingStarted('int-id-999', 'no-such-uuid');
        $this->assertNull($result);
    }

    // ──────────────────────────── meeting-ended ──────────────────────────────

    public function testMeetingEndedSetsEndedAt()
    {
        $session = $this->createSession('ws-ended', 'WS Ended');
        $meeting = $this->createMeeting($session, 'int-id-010');
        $p = $this->makeProcessor();

        $before = time();
        $result = $p->onMeetingEnded('int-id-010');
        $this->assertTrue($result);

        $meeting->refresh();
        $this->assertNotNull($meeting->ended_at);
        $this->assertGreaterThanOrEqual($before, $meeting->ended_at);
    }

    public function testMeetingEndedUnknownInternalIdReturnsFalse()
    {
        $p = $this->makeProcessor();
        $this->assertFalse($p->onMeetingEnded('int-id-does-not-exist'));
    }

    // ──────────────────────────── user-joined ────────────────────────────────

    public function testUserJoinedCreatesJoinRecord()
    {
        $session = $this->createSession('ws-join', 'WS Join');
        $meeting = $this->createMeeting($session, 'int-id-020');
        $p = $this->makeProcessor();

        $join = $p->onUserJoined('int-id-020', [
            'internal-user-id' => 'w_abc123',
            'external-user-id' => null,
            'name'             => 'Alice',
            'role'             => 'VIEWER',
        ]);

        $this->assertNotNull($join);
        $this->assertEquals('w_abc123', $join->bbb_internal_user_id);
        $this->assertEquals('Alice', $join->display_name);
        $this->assertEquals('viewer', $join->role);
        $this->assertNull($join->left_at);
    }

    public function testUserJoinedResolvesHumHubUserIdFromExternalUserId()
    {
        $session = $this->createSession('ws-join-user', 'WS Join User');
        $meeting = $this->createMeeting($session, 'int-id-021');
        $p = $this->makeProcessor();

        // Admin user has id=1 in fixture
        $join = $p->onUserJoined('int-id-021', [
            'internal-user-id' => 'w_def456',
            'external-user-id' => '1',
            'name'             => 'Admin',
            'role'             => 'MODERATOR',
        ]);

        $this->assertNotNull($join);
        $this->assertEquals(1, $join->user_id);
        $this->assertEquals('moderator', $join->role);
    }

    public function testUserJoinedIsIdempotent()
    {
        $session = $this->createSession('ws-join-idem', 'WS Join Idem');
        $meeting = $this->createMeeting($session, 'int-id-022');
        $p = $this->makeProcessor();

        $userAttrs = ['internal-user-id' => 'w_ghi789', 'name' => 'Bob', 'role' => 'VIEWER'];
        $p->onUserJoined('int-id-022', $userAttrs);
        $second = $p->onUserJoined('int-id-022', $userAttrs);

        $this->assertNull($second);
        $this->assertEquals(1, SessionMeetingJoin::find()->where(['bbb_internal_user_id' => 'w_ghi789'])->count());
    }

    // ──────────────────────────── user-left ──────────────────────────────────

    public function testUserLeftSetsLeftAt()
    {
        $session = $this->createSession('ws-left', 'WS Left');
        $meeting = $this->createMeeting($session, 'int-id-030');
        $p = $this->makeProcessor();

        $p->onUserJoined('int-id-030', ['internal-user-id' => 'w_jkl', 'name' => 'Carol', 'role' => 'VIEWER']);

        $before = time();
        $result = $p->onUserLeft('int-id-030', ['internal-user-id' => 'w_jkl']);
        $this->assertTrue($result);

        $join = SessionMeetingJoin::findOne(['bbb_internal_user_id' => 'w_jkl']);
        $this->assertNotNull($join->left_at);
        $this->assertGreaterThanOrEqual($before, $join->left_at);
    }

    // ──────────────────────────── chat-group-message-sent ────────────────────

    public function testChatMessageIsStoredFromBbb()
    {
        $session = $this->createSession('ws-chat', 'WS Chat');
        $meeting = $this->createMeeting($session, 'int-id-040');
        $p = $this->makeProcessor();

        // User joined first so we can resolve sender
        $p->onUserJoined('int-id-040', [
            'internal-user-id' => 'w_mno',
            'external-user-id' => '1',
            'name'             => 'Admin',
            'role'             => 'MODERATOR',
        ]);

        $chat = $p->onChatMessage('int-id-040', [
            'sender'  => ['internal-user-id' => 'w_mno', 'name' => 'Admin'],
            'message' => 'Hello from BBB!',
        ]);

        $this->assertNotNull($chat);
        $this->assertEquals('Hello from BBB!', $chat->message);
        $this->assertEquals(SessionMeetingChat::SOURCE_BBB, $chat->source);
        $this->assertEquals(1, $chat->user_id);
        $this->assertNotNull($chat->sent_at);
    }

    public function testChatMessageWithEmptyMessageReturnsNull()
    {
        $session = $this->createSession('ws-chat-empty', 'WS Chat Empty');
        $this->createMeeting($session, 'int-id-041');
        $p = $this->makeProcessor();

        $result = $p->onChatMessage('int-id-041', ['sender' => ['name' => 'X'], 'message' => '']);
        $this->assertNull($result);
    }

    public function testChatMessageForUnknownMeetingReturnsNull()
    {
        $p = $this->makeProcessor();
        $result = $p->onChatMessage('int-id-no-meeting', ['sender' => ['name' => 'X'], 'message' => 'Hi']);
        $this->assertNull($result);
    }

    // ──────────────────────────── process() dispatcher ───────────────────────

    public function testProcessDispatchesMeetingStarted()
    {
        $session = $this->createSession('ws-dispatch', 'WS Dispatch');
        $p = $this->makeProcessor();

        $p->process($this->buildEvent('meeting-started', 'int-id-050', $session->uuid));

        $meeting = SessionMeeting::findByInternalId('int-id-050');
        $this->assertNotNull($meeting);
        $this->assertEquals($session->id, $meeting->session_id);
    }

    public function testProcessIgnoresUnknownEventTypes()
    {
        $session = $this->createSession('ws-unknown', 'WS Unknown');
        $p = $this->makeProcessor();

        // Should not throw
        $p->process($this->buildEvent('some-unknown-event', 'int-id-060', $session->uuid));
        $this->assertTrue(true);
    }

    public function testProcessHandlesFullMeetingLifecycle()
    {
        $session = $this->createSession('ws-lifecycle', 'WS Lifecycle');
        $p = $this->makeProcessor();
        $intId = 'int-id-070';

        // 1. Meeting started
        $p->process($this->buildEvent('meeting-started', $intId, $session->uuid));
        $meeting = SessionMeeting::findByInternalId($intId);
        $this->assertNotNull($meeting);

        // 2. User joins
        $p->process($this->buildEvent('user-joined', $intId, $session->uuid, [
            'user' => ['internal-user-id' => 'w_pqr', 'external-user-id' => '1', 'name' => 'Admin', 'role' => 'MODERATOR'],
        ]));
        $join = SessionMeetingJoin::findOne(['session_meeting_id' => $meeting->id]);
        $this->assertNotNull($join);

        // 3. Chat message arrives
        $p->process($this->buildEvent('chat-group-message-sent', $intId, $session->uuid, [
            'chat-message' => ['sender' => ['internal-user-id' => 'w_pqr', 'name' => 'Admin'], 'message' => 'Hi all!'],
        ]));
        $chat = SessionMeetingChat::findOne(['session_meeting_id' => $meeting->id]);
        $this->assertNotNull($chat);
        $this->assertEquals('Hi all!', $chat->message);

        // 4. User leaves
        $p->process($this->buildEvent('user-left', $intId, $session->uuid, [
            'user' => ['internal-user-id' => 'w_pqr'],
        ]));
        $join->refresh();
        $this->assertNotNull($join->left_at);

        // 5. Meeting ended
        $p->process($this->buildEvent('meeting-ended', $intId, $session->uuid));
        $meeting->refresh();
        $this->assertNotNull($meeting->ended_at);
    }
}
