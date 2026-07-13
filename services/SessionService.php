<?php
namespace k7zz\humhub\bbb\services;

use k7zz\humhub\bbb\models\Session;
use k7zz\humhub\bbb\models\SessionMeeting;
use k7zz\humhub\bbb\notifications\WebhookMissing;
use Yii;
use BigBlueButton\BigBlueButton;
use BigBlueButton\Parameters\{
    CreateMeetingParameters,
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
    /**
     * @var BigBlueButton BBB API client instance
     */
    private BigBlueButton $bbb;

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
        $users  = [];

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
        if ($session !== null) {
            $hasOpenMeeting = SessionMeeting::find()
                ->where(['session_id' => $session->id, 'ended_at' => null])
                ->exists();
            if ($hasOpenMeeting) {
                return true;
            }
        }

        return (bool) Yii::$app->cache->getOrSet(
            'bbb_is_running_live_' . $uuid,
            fn() => $this->bbb
                ->isMeetingRunning(new IsMeetingRunningParameters($uuid))
                ->isRunning(),
            15
        );
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
            $presentationUrl = Url::to('/bbb/public/download', true) . "?id=" . $s->id . "&type=presentation";

            $p->addPresentation($presentationUrl, file_get_contents($presentationUrl), $s->name . "_presentation.pdf");
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

}
