<?php
namespace k7zz\humhub\bbb\services;

use k7zz\humhub\bbb\models\Session;
use Yii;
use BigBlueButton\BigBlueButton;
use BigBlueButton\Parameters\{
    CreateMeetingParameters,
    IsMeetingRunningParameters,
    JoinMeetingParameters,
    GetRecordingsParameters,
    PublishRecordingsParameters,
    UpdateRecordingsParameters
};
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
    private function getQueryStarter(ContentContainerActiveRecord $container = null)
    {
        return Session::find()->contentContainer($container);
    }

    /* ------------------------------------------------------------------ */
    /*  API-Methoden                                                      */
    /* ------------------------------------------------------------------ */

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
    public function get(?int $id = null, ContentContainerActiveRecord $container = null): ?Session
    {
        if ($id === null) {
            return null;
        }

        $query = $this->getQueryStarter($container)
            ->alias('session')
            ->joinWith('content')
            ->where(['session.id' => $id, 'session.deleted_at' => null]);

        return $query->one();
    }

    /**
     * Checks if a meeting with the given UUID is currently running on BBB.
     * @param string $uuid
     * @return bool
     */
    public function isRunning(string $uuid): bool
    {
        if (empty($uuid)) {
            Yii::error("UUID is empty, cannot check if meeting is running", 'bbb');
            return false; // UUID ist leer, also kann es nicht laufen
        }
        return $this->bbb
            ->isMeetingRunning(new IsMeetingRunningParameters($uuid))
            ->isRunning();
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
        $p = (new CreateMeetingParameters($s->uuid, $s->title))
            ->setRecord((bool) $s->allow_recording)
            ->setAllowStartStopRecording((bool) $s->allow_recording)
            ->setWelcome($description)
            ->setMuteOnStart((bool) $s->mute_on_entry)
            ->setAllowModsToUnmuteUsers(true)
            ->setAllowModsToEjectCameras(true)
            ->setMeetingKeepEvents(true)
            ->setGuestPolicy(
                $s->has_waitingroom ? "ASK_MODERATOR" : "ALWAYS_ACCEPT"
            )
            ->setLogoutURL(Yii::$app->urlManager->createAbsoluteUrl($exitUrl . "?highlight=" . $s->id))
            ->setMeetingLayout($s->layout);

        if ($s->presentation_file_id > 0) {
            $presentationUrl = Url::to('/bbb/public/download', true) . "?token=" . $s->public_token . "&type=presentation";

            $p->addPresentation($presentationUrl, file_get_contents($presentationUrl), $s->name . "_presentation.pdf");
        }

        $r = $this->bbb->createMeeting($p);          // mehrfach aufrufbar
        if (!$r->success()) {
            Yii::error("BBB-CreateMeeting failed for session {$s->name} ({$s->id}): " . $r->getMessage(), 'bbb');
            return null;
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
            ->setUserID(Yii::$app->user->identity->email);
        if (Yii::$app->user->identity->getProfileImage())
            $jp->setAvatarURL(Url::to(Yii::$app->user->identity->getProfileImage()->getUrl(), true));
        return $this->bbb->getJoinMeetingURL($jp);
    }

    public function anonymousJoinUrl(Session $session, string $displayName): string
    {
        $jp = (new JoinMeetingParameters($session->uuid, $displayName, Role::VIEWER))
            ->setUserID(UUID::v4());
        return $this->bbb->getJoinMeetingURL($jp);
    }

    /**
     * Retrieves all recordings for a session, if the user can administer it.
     * @param int|null $id
     * @param ContentContainerActiveRecord|null $container
     * @return array
     */
    public function getRecordings(?int $id = null, ContentContainerActiveRecord $container = null): array
    {
        $session = $this->get($id, $container);
        if (!$session) {
            return [];
        }
        if (!$session->canAdminister())
            return []; // ATM only for admins

        $params = new GetRecordingsParameters();
        $params->setMeetingID($session->uuid);
        if (!$session->canAdminister())
            $params->setState('published'); // nur veröffentlichte Aufzeichnungen
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
     * Publishes or unpublishes a BBB recording by its record ID.
     * @param string $recordId
     * @param bool $publish
     * @return bool
     */
    public function publishRecording(string $recordId, bool $publish = false): bool
    {
        $params = new PublishRecordingsParameters($recordId);
        $params->setPublish($publish);

        try {
            $response = $this->bbb->publishRecordings($params);
            return $response->getReturnCode() === 'SUCCESS';
        } catch (\Exception $e) {
            Yii::error("BBB-PublishRecordings failed for record {$recordId}: " . $e->getMessage(), 'bbb');
            return false;
        }
    }

}
