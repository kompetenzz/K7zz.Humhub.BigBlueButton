<?php
namespace k7zz\humhub\bbb\services;

use k7zz\humhub\bbb\models\Session;
use k7zz\humhub\bbb\models\SessionMeeting;
use k7zz\humhub\bbb\models\SessionMeetingChat;
use k7zz\humhub\bbb\notifications\WebhookMissing;
use Yii;
use BigBlueButton\BigBlueButton;
use BigBlueButton\Parameters\{
    CreateMeetingParameters,
    DeleteRecordingsParameters,
    EndMeetingParameters,
    GetMeetingInfoParameters,
    HooksCreateParameters,
    IsMeetingRunningParameters,
    JoinMeetingParameters,
    GetRecordingsParameters,
    PublishRecordingsParameters,
    SendChatMessageParameters,
    UpdateRecordingsParameters
};
use k7zz\humhub\bbb\models\RecordingFormat;
use BigBlueButton\Enum\Role;
use humhub\modules\content\components\ContentContainerActiveRecord;
use yii\helpers\Url;
use humhub\libs\UUID;
use yii\httpclient\Client;

/**
 * Service class for handling BigBlueButton (BBB) session logic in HumHub.
 *
 * This service provides methods to:
 * - List, retrieve, and delete BBB sessions
 * - Start and join meetings
 * - Check if a meeting is running
 * - Manage and publish recordings
 *
 * The BBB server URL and secret are loaded from the module settings.
 */
class SessionService
{
    private const LIVE_RUNNING_CACHE_SECONDS = 15;
    private const LOCAL_RUNNING_VERIFY_SECONDS = 300;
    private const LIVE_INFO_CACHE_SECONDS = 20;
    // How long a meeting found lost in akka-apps is treated as not running
    // (unless it is started again or BBB reports a new meeting-started)
    private const LOST_MEETING_SECONDS = 3600;
    private const PROBE_USER_NAME = 'HumHub BBB health check';
    private const PROBE_TIMEOUT = 5;
    // Minimum time between two probes of the same meeting from the regular polling
    private const PROBE_INTERVAL = 60;
    // The probe user's credentials are reused as long as they work, so that not
    // every probe registers another (hidden) user in the meeting
    private const PROBE_CREDENTIALS_SECONDS = 43200;

    /**
     * @var BigBlueButton BBB API client instance
     */
    private BigBlueButton $bbb;

    /**
     * @var string Scheme and host of the BBB server, e.g. https://bbb.example.com
     */
    private string $serverRoot;

    /**
     * Initializes the BBB API client using module settings.
     */
    public function __construct()
    {
        /* ---------- Settings laden ---------- */
        $settings = Yii::$app->getModule('bbb')->settings;
        $baseUrl = rtrim($settings->get('bbbUrl') ?? '', '/') . '/';
        $secret = $settings->get('bbbSecret') ?? '';

        $this->bbb = new BigBlueButton($baseUrl, $secret);
        $parts = parse_url($baseUrl) ?: [];
        $this->serverRoot = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '')
            . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }

    /**
     * Returns a query for sessions, optionally filtered by content container.
     * @param ContentContainerActiveRecord|null $container
     * @return \yii\db\ActiveQuery
     */
    private function getQueryStarter(?ContentContainerActiveRecord $container = null, bool $everyWhere = false)
    {
        if ($everyWhere) {
            return Session::find();
        }
        return Session::find()
            ->contentContainer($container);
    }

    /* ------------------------------------------------------------------ */
    /*  API-Methoden                                                      */
    /* ------------------------------------------------------------------ */

    /**
     * Returns all sessions across all containers, grouped by type.
     * Returns ['global' => Session[], 'spaces' => [spaceId => ['container' => Space, 'sessions' => Session[]]], 'users' => [...]]
     */
    public function listAllGrouped(): array
    {
        $all = $this->getQueryStarter(null, true)
            ->alias('session')
            ->joinWith('content')
            ->where(['session.deleted_at' => null])
            ->all();

        $global = [];
        $spaces = [];
        $users = [];

        foreach ($all as $session) {
            $container = $session->content->container ?? null;

            if ($container === null) {
                $global[] = $session;
            } elseif ($container instanceof \humhub\modules\space\models\Space) {
                $id = $container->id;
                if (!isset($spaces[$id])) {
                    $spaces[$id] = ['container' => $container, 'sessions' => []];
                }
                $spaces[$id]['sessions'][] = $session;
            } else {
                // User profile or other container
                $id = $container->id;
                if (!isset($users[$id])) {
                    $users[$id] = ['container' => $container, 'sessions' => []];
                }
                $users[$id]['sessions'][] = $session;
            }
        }

        return compact('global', 'spaces', 'users');
    }

    /**
     * Returns a list of all sessions, optionally filtered by content container and enabled status.
     * @param ContentContainerActiveRecord|null $container
     * @param bool $onlyEnabled
     * @return Session[]
     */
    public function list(ContentContainerActiveRecord $container = null, bool $onlyEnabled = false): array
    {
        $query = $this->getQueryStarter($container)
            ->alias('session')
            ->joinWith('content')
            ->where(['session.deleted_at' => null]);

        if ($onlyEnabled) {
            $query->andWhere(['session.enabled' => true]);
        }
        //Yii::error("Query: " . $query->createCommand()->getRawSql(), 'bbb');
        $result = $query->all();
        //Yii::error("Result: " . count($result), 'bbb');
        return $result;
    }

    /**
     * Retrieves a single session by ID, optionally filtered by content container.
     * @param int|null $id
     * @param ContentContainerActiveRecord|null $container
     * @return Session|null
     */
    public function get(?int $id = null, ContentContainerActiveRecord $container = null, bool $everyWhere = false): ?Session
    {
        if ($id === null) {
            return null;
        }

        $query = $this->getQueryStarter($container, $everyWhere)
            ->alias('session')
            ->joinWith('content')
            ->where(['session.id' => $id, 'session.deleted_at' => null]);

        return $query->one();
    }

    /**
     * Checks if a meeting with the given UUID is currently running.
     *
     * Answered from the local `bbb_session_meeting` table (kept up to date via the
     * BBB meeting-started/meeting-ended webhook) whenever possible, so that polling
     * from many clients doesn't translate into live BBB API calls. Only falls back
     * to asking BBB directly if the local state doesn't show a running meeting,
     * which could mean the webhook was never delivered; that fallback is throttled
     * per session so concurrent pollers collapse into a single BBB request.
     * @param string $uuid
     * @return bool
     */
    public function isRunning(string $uuid): bool
    {
        if (empty($uuid)) {
            Yii::error("UUID is empty, cannot check if meeting is running", 'bbb');
            return false; // UUID ist leer, also kann es nicht laufen
        }

        $session = Session::findOne(['uuid' => $uuid]);
        if ($session !== null && $this->isMarkedLost($session)) {
            return false;
        }
        if ($session !== null) {
            $hasOpenMeeting = SessionMeeting::find()
                ->where(['session_id' => $session->id, 'ended_at' => null])
                ->exists();
            if ($hasOpenMeeting) {
                $verifyKey = 'bbb_is_running_verified_' . $uuid;
                if (Yii::$app->cache->get($verifyKey) === true) {
                    return $this->confirmUsable($session);
                }

                $running = $this->requestRunningStatus($uuid, $session);
                if ($running === null) {
                    return true;
                }

                if ($running) {
                    Yii::$app->cache->set($verifyKey, true, self::LOCAL_RUNNING_VERIFY_SECONDS);
                    Yii::$app->cache->set('bbb_is_running_live_' . $uuid, ['running' => true], self::LIVE_RUNNING_CACHE_SECONDS);
                    return $this->confirmUsable($session);
                }

                $this->markNotRunning($session);
                return false;
            }
        }

        $cacheKey = 'bbb_is_running_live_' . $uuid;
        $cached = Yii::$app->cache->get($cacheKey);
        if (is_array($cached) && array_key_exists('running', $cached)) {
            $running = (bool) $cached['running'];
        } else {
            $running = $this->requestRunningStatus($uuid, $session);
            if ($running === null) {
                return false;
            }
            Yii::$app->cache->set($cacheKey, ['running' => $running], self::LIVE_RUNNING_CACHE_SECONDS);
        }

        return $running && ($session === null || $this->confirmUsable($session));
    }

    public function refreshRunningStatus(Session $session): bool
    {
        if (empty($session->uuid)) {
            return false;
        }

        $running = $this->requestRunningStatus($session->uuid, $session);
        if ($running === null) {
            return $this->isRunning($session->uuid);
        }

        if ($running && !$this->confirmUsable($session, force: true)) {
            return false;
        }

        Yii::$app->cache->set('bbb_is_running_live_' . $session->uuid, ['running' => $running], self::LIVE_RUNNING_CACHE_SECONDS);
        if ($running) {
            Yii::$app->cache->set('bbb_is_running_verified_' . $session->uuid, true, self::LOCAL_RUNNING_VERIFY_SECONDS);
        }
        if (!$running) {
            $this->markNotRunning($session);
        }

        return $running;
    }

    /**
     * Checks that a meeting bbb-web reports as running is really usable (see
     * probeMeetingUsable()) and handles it as ended if not. Called from the regular
     * polling too, so it is throttled per session and concurrent pollers don't pile up.
     * @param bool $force probe right now (e.g. right before joining)
     * @return bool false if the meeting turned out to be lost
     */
    private function confirmUsable(Session $session, bool $force = false): bool
    {
        $okKey = 'bbb_probe_ok_' . $session->uuid;
        $lockKey = 'bbb_probe_lock_' . $session->uuid;
        if (!$force) {
            if (Yii::$app->cache->get($okKey) !== false) {
                return true;
            }
            // Another request is probing already - keep the last known state meanwhile
            if (!Yii::$app->cache->add($lockKey, 1, self::PROBE_TIMEOUT * 3)) {
                return true;
            }
        }

        $usable = $this->probeMeetingUsable($session);
        if (!$force) {
            Yii::$app->cache->delete($lockKey);
        }
        if ($usable === false) {
            $this->handleLostMeeting($session);
            return false;
        }
        // Inconclusive results are throttled as well, BBB may just be slow or old
        Yii::$app->cache->set($okKey, time(), self::PROBE_INTERVAL);
        return true;
    }

    /**
     * bbb-web may keep reporting a meeting as running although akka-apps has lost it
     * (e.g. after an akka-apps restart or a lost "meeting ended" event). Joining then
     * fails inside the client ("Ooops...", akka-apps logs "Meeting not found").
     * Only the client's own session check reveals this, so a hidden probe user fetches
     * the client settings exactly like the HTML5 client does.
     * @return bool|null true = usable, false = lost in akka-apps, null = inconclusive
     *                   (e.g. BBB < 3.0 without that endpoint, network problems)
     */
    private function probeMeetingUsable(Session $session): ?bool
    {
        $credentialsKey = 'bbb_probe_credentials_' . $session->uuid;
        $credentials = Yii::$app->cache->get($credentialsKey);
        if (is_array($credentials)) {
            if ($this->probeClientSettings($session, $credentials) === true) {
                return true;
            }
            // The credentials may just be stale (e.g. from an earlier meeting),
            // so only a fresh probe join is conclusive
            Yii::$app->cache->delete($credentialsKey);
        }

        $credentials = $this->probeJoin($session);
        if ($credentials === null) {
            return null;
        }
        $usable = $this->probeClientSettings($session, $credentials);
        if ($usable === true) {
            Yii::$app->cache->set($credentialsKey, $credentials, self::PROBE_CREDENTIALS_SECONDS);
        }
        return $usable;
    }

    /**
     * Registers the hidden probe user (as moderator, to skip the guest lobby).
     * @return array{token:string,cookie:string}|null
     */
    private function probeJoin(Session $session): ?array
    {
        try {
            $jp = (new JoinMeetingParameters($session->uuid, self::PROBE_USER_NAME, Role::MODERATOR))
                ->setUserID('humhub-probe')
                ->setRedirect(false)
                ->setExcludeFromDashboard(true);
            $join = (new Client())->get($this->bbb->getJoinMeetingURL($jp), null, [], ['timeout' => self::PROBE_TIMEOUT])->send();
            $xml = $join->isOk ? @simplexml_load_string($join->content) : false;
            $token = $xml ? (string) $xml->session_token : '';
            if ($token === '') {
                return null;
            }
            // The session check needs the JSESSIONID cookie of the join as well
            $cookies = [];
            foreach ($join->cookies as $cookie) {
                $cookies[] = $cookie->name . '=' . $cookie->value;
            }
            return ['token' => $token, 'cookie' => implode('; ', $cookies)];
        } catch (\Throwable $e) {
            Yii::warning("BBB probe join failed for session {$session->name} ({$session->id}): " . $e->getMessage(), 'bbb');
            return null;
        }
    }

    /**
     * @param array{token:string,cookie:string} $credentials
     * @return bool|null see probeMeetingUsable()
     */
    private function probeClientSettings(Session $session, array $credentials): ?bool
    {
        try {
            $response = (new Client())->get(
                $this->serverRoot . '/api/rest/clientSettings',
                null,
                ['x-session-token' => $credentials['token'], 'Cookie' => $credentials['cookie']],
                ['timeout' => self::PROBE_TIMEOUT]
            )->send();
            if ($response->isOk && str_contains($response->content, 'meeting_clientSettings')) {
                return true;
            }
            // The auth hook got no user back from akka-apps
            if (str_contains($response->content, 'x-hasura-role')) {
                return false;
            }
            return null;
        } catch (\Throwable $e) {
            Yii::warning("BBB probe failed for session {$session->name} ({$session->id}): " . $e->getMessage(), 'bbb');
            return null;
        }
    }

    private function isMarkedLost(Session $session): bool
    {
        return Yii::$app->cache->get('bbb_meeting_lost_' . $session->uuid) !== false;
    }

    /**
     * Clears the "lost meeting" marker, e.g. when a new meeting was started.
     */
    public function clearLostMarker(Session $session): void
    {
        Yii::$app->cache->delete('bbb_meeting_lost_' . $session->uuid);
    }

    /**
     * Treats a meeting lost in akka-apps as ended and asks BBB to end it, so that
     * bbb-web forgets it and the session can be started again.
     */
    private function handleLostMeeting(Session $session): void
    {
        Yii::error("BBB meeting of session {$session->name} ({$session->id}) is reported running by bbb-web but unknown to akka-apps - treating it as ended", 'bbb');
        Yii::$app->cache->set('bbb_meeting_lost_' . $session->uuid, time(), self::LOST_MEETING_SECONDS);
        Yii::$app->cache->delete('bbb_probe_ok_' . $session->uuid);
        Yii::$app->cache->delete('bbb_probe_credentials_' . $session->uuid);
        $this->markNotRunning($session);
        try {
            $this->bbb->endMeeting(new EndMeetingParameters($session->uuid));
        } catch (\Throwable $e) {
            Yii::warning("BBB-EndMeeting for lost meeting of session {$session->name} ({$session->id}) failed: " . $e->getMessage(), 'bbb');
        }
    }

    private function requestRunningStatus(string $uuid, ?Session $session = null): ?bool
    {
        try {
            return $this->bbb
                ->isMeetingRunning(new IsMeetingRunningParameters($uuid))
                ->isRunning();
        } catch (\Throwable $e) {
            $label = $session ? "{$session->name} ({$session->id})" : $uuid;
            Yii::warning("BBB-IsMeetingRunning failed for session {$label}: " . $e->getMessage(), 'bbb');
            return null;
        }
    }

    /**
     * Returns live statistics of a running meeting (participants, audio, video, recording, start time).
     *
     * Fetched via getMeetingInfo and cached per session, so that many polling clients
     * collapse into one BBB request per cache period. Returns null if the meeting isn't
     * running or BBB can't be reached.
     * @return array{participants:int,moderators:int,video:int,recording:bool,startTime:int}|null
     */
    public function getLiveInfo(Session $session): ?array
    {
        if (empty($session->uuid)) {
            return null;
        }

        $info = Yii::$app->cache->getOrSet(
            'bbb_live_info_' . $session->uuid,
            function () use ($session) {
                try {
                    $response = $this->bbb->getMeetingInfo(new GetMeetingInfoParameters($session->uuid));
                    if (!$response->success()) {
                        return null;
                    }
                    $meeting = $response->getMeeting();
                    if (!$meeting->isRunning()) {
                        return null;
                    }
                    return [
                        'participants' => $meeting->getParticipantCount(),
                        'moderators' => $meeting->getModeratorCount(),
                        'video' => $meeting->getVideoCount(),
                        'startTime' => (int) floor($meeting->getStartTime() / 1000),
                    ];
                } catch (\Throwable $e) {
                    Yii::warning("BBB-GetMeetingInfo failed for session {$session->name} ({$session->id}): " . $e->getMessage(), 'bbb');
                    return null;
                }
            },
            self::LIVE_INFO_CACHE_SECONDS
        );
        if ($info === null) {
            return null;
        }

        // BBB's <recording> only says the meeting *may* be recorded, not that it currently is.
        // The actual state is only known from the recording-started/-stopped webhooks.
        $info['recording'] = $this->isRecordingActive($session);
        return $info;
    }

    private function isRecordingActive(Session $session): bool
    {
        $last = SessionMeetingChat::find()
            ->alias('c')
            ->innerJoin(SessionMeeting::tableName() . ' m', 'm.id = c.session_meeting_id')
            ->where([
                'm.session_id' => $session->id,
                'm.ended_at' => null,
                'c.source' => SessionMeetingChat::SOURCE_SYSTEM,
                'c.message' => ['recording-started', 'recording-stopped'],
            ])
            ->orderBy(['c.id' => SORT_DESC])
            ->select('c.message')
            ->scalar();
        return $last === 'recording-started';
    }

    public function markNotRunning(Session $session, ?int $endedAt = null): int
    {
        $endedAt ??= time();
        $count = SessionMeeting::updateAll(
            ['ended_at' => $endedAt],
            ['session_id' => $session->id, 'ended_at' => null]
        );

        Yii::$app->cache->set('bbb_is_running_live_' . $session->uuid, ['running' => false], self::LIVE_RUNNING_CACHE_SECONDS);
        Yii::$app->cache->delete('bbb_is_running_verified_' . $session->uuid);

        if ($count > 0) {
            (new SessionMeetingChat([
                'session_id' => $session->id,
                'session_meeting_id' => null,
                'source' => SessionMeetingChat::SOURCE_SYSTEM,
                'message' => 'meeting-ended',
                'sender_name' => '',
                'created_at' => $endedAt,
            ]))->save();
        }

        return $count;
    }

    public function sendChatToMeeting(Session $session, string $message, string $userName): bool
    {
        $params = new SendChatMessageParameters($session->uuid, $message, $userName . \k7zz\humhub\bbb\models\SessionMeetingChat::BBB_MSG_SUFFIX);
        return $this->bbb->getSendChatMessage($params)->success();
    }

    /**
     * Starts a new BBB session (idempotent) and returns the moderator join URL.
     * @param Session $s
     * @param ContentContainerActiveRecord|null $container
     * @return string Moderator join URL
     */
    public function start(Session $s, ContentContainerActiveRecord $container = null): string|null
    {
        $exitUrl = $container ? $container->createUrl('/bbb/session/exit') :
            Url::to('/bbb/session/exit');
        $anonymousJoinUrl = Url::to('/bbb/public/join/' . $s->public_token, true);
        $description = $s->description ?? '';
        if ($s->public_token && $s->public_join) {
            $description .= "\n\n<br><br>" . Yii::t('BbbModule.base', 'Public join link for this session: <a href="{link}">{link}</a>', [
                'link' => $anonymousJoinUrl
            ]);
        }
        $moderatorInfo = Yii::t(
            'BbbModule.base',
            'You are the moderator of this session. As such, you have additional permissions and responsibilities compared to regular participants.'
            . ' Moderators can not be randomly assigned to breakout rooms!'
        );

        $moderatorInfo .= ($s->has_waitingroom ?
            Yii::t('BbbModule.base', ' Participants will be placed in the waiting room until a moderator accepts them.') :
            Yii::t('BbbModule.base', ' Participants will enter directly.'));

        $p = (new CreateMeetingParameters($s->uuid, $s->title))
            ->setRecord((bool) $s->allow_recording)
            ->setAllowStartStopRecording((bool) $s->allow_recording)
            ->setWelcome($description)
            ->setMuteOnStart((bool) $s->mute_on_entry)
            ->setAllowModsToUnmuteUsers(true)
            ->setAllowModsToEjectCameras(true)
            ->setAllowPromoteGuestToModerator(true)
            ->setBreakout(false)
            ->setMeetingKeepEvents(true)
            ->setGuestPolicy(
                $s->has_waitingroom ? "ASK_MODERATOR" : "ALWAYS_ACCEPT"
            )
            ->setModeratorOnlyMessage($moderatorInfo)
            ->setLogoutURL(Yii::$app->urlManager->createAbsoluteUrl($exitUrl . "?highlight=" . $s->id))
            ->setMeetingLayout($s->layout);

        if ($s->presentation_file_id > 0) {
            $presentationFile = $s->getPresentationFile();
            if ($presentationFile === null || !$presentationFile->store->has()) {
                throw new \RuntimeException("BBB presentation file for session {$s->id} is missing.");
            }

            $store = $presentationFile->store;
            $presentationContent = method_exists($store, 'getContent')
                ? $store->getContent()
                : file_get_contents($store->get());
            $p->addPresentation($s->name . "_presentation.pdf", $presentationContent);
        }

        // createMeeting on a meeting that bbb-web still keeps (although lost in akka-apps)
        // would just return that broken meeting again
        if ($this->isMarkedLost($s)) {
            if ($this->requestRunningStatus($s->uuid, $s)) {
                Yii::error("BBB: cannot start session {$s->name} ({$s->id}) - bbb-web still keeps the lost meeting, restarting bbb-web on the BBB server is required", 'bbb');
                return null;
            }
            $this->clearLostMarker($s);
        }

        // Register webhook before createMeeting so meeting-started fires into an already-registered hook
        $hookRegistered = $this->registerMeetingWebhook($s);

        if (!$hookRegistered) {
            // Surface the problem right inside BBB: moderatorOnlyMessage is shown
            // in the chat to every (also later joining) moderator, never to participants.
            // Only effective when this createMeeting actually creates the meeting —
            // BBB ignores all params when the meeting is already running.
            Yii::warning("BBB: appending webhook-failure warning to moderatorOnlyMessage for session {$s->name} ({$s->id})", 'bbb');
            $p->setModeratorOnlyMessage(
                $moderatorInfo . "\n\n<br><br><b>⚠️ " . Yii::t(
                    'BbbModule.base',
                    'Warning: Webhook registration with the BBB server failed. Chat integration and meeting status tracking in HumHub will not work for this meeting. Please check the bbb-webhooks service.'
                ) . '</b>'
            );
        }

        $r = $this->bbb->createMeeting($p);
        if (!$r->success()) {
            Yii::error("BBB-CreateMeeting failed for session {$s->name} ({$s->id}): " . $r->getMessage(), 'bbb');
            return null;
        }

        if (!$hookRegistered) {
            WebhookMissing::notifyModerators($s);
            // Flag for the session page poller so moderators get an inline banner
            // without waiting for the (slow) live-poll notification delivery
            Yii::$app->cache->set('bbb:hook_failed:' . $s->id, time(), 600);
        }

        return $this->joinUrl($s, true);
    }

    /**
     * Builds a join URL for the current user for the given session.
     * @param Session $session
     * @param bool $moderator
     * @return string
     */
    public function joinUrl(Session $session, bool $moderator = false): string
    {
        $jp = (new JoinMeetingParameters(
            $session->uuid,
            Yii::$app->user->identity->displayName,
            $moderator ? Role::MODERATOR : Role::VIEWER
        ))
            ->setUserID((string) Yii::$app->user->identity->id);
        if (Yii::$app->user->identity->getProfileImage()) {
            $jp->setAvatarURL(Url::to(Yii::$app->user->identity->getProfileImage()->getUrl(), true));
        }
        if ($session->camera_bg_image_file_id > 0) {
            $cameraBgImageUrl = Url::to('/bbb/public/download', true)
                . "?id=" . $session->id
                . "&type=camera-bg-image&inline=true&embeddable=true";
            $jp->setWebcamBackgroundURL($cameraBgImageUrl);
        }

        if ($session->start_participants_minimized) {
            $jp->addUserData('bbb_show_participants_on_login', false);
        } elseif ($session->start_chat_minimized) {
            $jp->addUserData('bbb_show_public_chat_on_login', false);
        }
        if ($session->start_presentation_hidden) {
            $jp->addUserData('bbb_hide_presentation_on_join', true);
        }

        return $this->bbb->getJoinMeetingURL($jp);
    }

    /**
     * Registers the BBB meeting-lifecycle webhook for the given session's meeting.
     *
     * Always registered (independent of the BBB-chat setting) because
     * meeting-started/meeting-ended events are also how isRunning() tracks state
     * locally without hitting the BBB API on every poll. Chat events still arrive
     * for sessions with chat integration off, but are simply never surfaced since
     * the chat UI isn't rendered for them.
     */
    private function registerMeetingWebhook(Session $session): bool
    {
        $callbackUrl = Yii::$app->urlManager->createAbsoluteUrl(['/bbb/webhook/receive']);
        Yii::warning("BBB-HooksCreate: registering hook for session {$session->name} ({$session->id}) → {$callbackUrl}", 'bbb');

        $hp = (new HooksCreateParameters($callbackUrl))
            ->setMeetingID($session->uuid);

        try {
            $result = $this->bbb->hooksCreate($hp);
            if (!$result->success()) {
                Yii::error("BBB-HooksCreate failed for session {$session->name} ({$session->id}): " . $result->getMessage(), 'bbb');
                return false;
            }
            Yii::warning("BBB-HooksCreate: hook registered successfully (hookId=" . $result->getHookId() . ")", 'bbb');
            return true;
        } catch (\Throwable $e) {
            Yii::warning("BBB-HooksCreate response parse error for session {$session->name}: " . $e->getMessage(), 'bbb');
            return false;
        }
    }

    public function anonymousJoinUrl(Session $session, string $displayName): string
    {
        $jp = (new JoinMeetingParameters($session->uuid, $displayName, Role::VIEWER))
            ->setUserID(UUID::v4());
        return $this->bbb->getJoinMeetingURL($jp);
    }

    /**
     * Retrieves recordings for a session.
     * Admins see all recordings, members only published ones.
     * @param int|null $id
     * @param ContentContainerActiveRecord|null $container
     * @return array
     */
    public function hasRecordings(Session $session): bool
    {
        return (bool) Yii::$app->cache->getOrSet(
            'bbb:has_recordings:' . $session->id,
            function () use ($session) {
                try {
                    $params = new GetRecordingsParameters();
                    $params->setMeetingID($session->uuid);
                    $response = $this->bbb->getRecordings($params);
                    return $response && $response->success() && count($response->getRecords()) > 0;
                } catch (\Exception $e) {
                    return false;
                }
            },
            60
        );
    }

    public function getRecordings(?int $id = null, ?ContentContainerActiveRecord $container = null): array
    {
        $session = $this->get($id, $container);
        if (!$session) {
            return [];
        }

        $params = new GetRecordingsParameters();
        $params->setMeetingID($session->uuid);
        try {
            $response = $this->bbb->getRecordings($params);
            if ($response && $response->success()) {
                return $response->getRecords();
            }
        } catch (\Exception $e) {
            Yii::error("BBB-GetRecordings failed for session {$session->name}: " . $e->getMessage(), 'bbb');
        }
        return [];
    }

    /**
     * Soft-deletes a session by setting its deleted_at timestamp.
     * @param int|null $id
     * @param ContentContainerActiveRecord|null $container
     * @return bool|null
     */
    public function delete(?int $id = null, ContentContainerActiveRecord $container = null): ?bool
    {
        if ($id === null) {
            return null;
        }

        $query = $this->getQueryStarter($container)
            ->alias('session')
            ->joinWith('content')
            ->where(['session.id' => $id, 'session.deleted_at' => null]);

        $session = $query->one();
        if ($session) {
            $session->deleted_at = time();
            return $session->save();
        }
        return false;
    }

    /**
     * Publishes or unpublishes a single format of a BBB recording.
     * Visibility is tracked in our own DB (bbb_recording_format).
     * @param string $recordId   BBB record ID
     * @param string $formatType e.g. 'presentation', 'video'
     * @param bool   $publish
     * @return bool
     */
    public function publishRecordingFormat(string $recordId, string $formatType, bool $publish): bool
    {
        return RecordingFormat::setPublished($recordId, $formatType, $publish);
    }

    /**
     * Permanently deletes a BBB recording for the given session.
     * Also removes local per-format visibility rows for the deleted recording.
     * @param Session $session
     * @param string $recordId
     * @return bool
     */
    public function deleteRecording(Session $session, string $recordId): bool
    {
        try {
            $recordingsParams = new GetRecordingsParameters();
            $recordingsParams->setMeetingID($session->uuid);
            $recordingsResponse = $this->bbb->getRecordings($recordingsParams);

            if (!$recordingsResponse || !$recordingsResponse->success()) {
                Yii::warning("BBB-DeleteRecordings pre-check failed for session {$session->name}: cannot load recordings list", 'bbb');
                return false;
            }

            $belongsToSession = false;
            foreach ($recordingsResponse->getRecords() as $record) {
                if ($record->getRecordId() === $recordId) {
                    $belongsToSession = true;
                    break;
                }
            }

            if (!$belongsToSession) {
                Yii::warning("BBB-DeleteRecordings denied: record {$recordId} does not belong to session {$session->name} ({$session->id})", 'bbb');
                return false;
            }

            $params = new DeleteRecordingsParameters($recordId);
            $response = $this->bbb->deleteRecordings($params);
            $deleted = $response && $response->success() && $response->isDeleted();

            if ($deleted) {
                RecordingFormat::deleteAll(['record_id' => $recordId]);
            }

            return $deleted;
        } catch (\Throwable $e) {
            Yii::error("BBB-DeleteRecordings failed for record {$recordId}: " . $e->getMessage(), 'bbb');
            return false;
        }
    }

}
