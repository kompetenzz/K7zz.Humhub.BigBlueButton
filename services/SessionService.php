<?php
namespace k7zz\humhub\bbb\services;

use k7zz\humhub\bbb\models\Session;
use Yii;
use BigBlueButton\BigBlueButton;
use BigBlueButton\Parameters\{
    CreateMeetingParameters,
    IsMeetingRunningParameters,
    JoinMeetingParameters
};
use humhub\modules\content\components\ContentContainerActiveRecord;
use yii\helpers\Url;

/**
 * Domaindienst rund um BBB-Sessions.
 * Die BBB-Serverdaten werden aus den Modul-Settings geladen.
 */
class SessionService
{
    private BigBlueButton $bbb;

    public function __construct()
    {
        /* ---------- Settings laden ---------- */
        $settings = Yii::$app->getModule('bbb')->settings;
        $baseUrl = rtrim($settings->get('bbbUrl') ?? '', '/') . '/';
        $secret = $settings->get('bbbSecret') ?? '';

        $this->bbb = new BigBlueButton($baseUrl, $secret);
    }

    private function getQueryStarter(ContentContainerActiveRecord $container = null)
    {
        return Session::find()->contentContainer($container);
    }

    /* ------------------------------------------------------------------ */
    /*  API-Methoden                                                      */
    /* ------------------------------------------------------------------ */

    /** Liste aller Sessions – optional nach ContentContainer gefiltert */
    public function list(ContentContainerActiveRecord $container = null, bool $onlyEnabled = false): array
    {
        $query = $this->getQueryStarter($container)
            ->alias('session')
            ->joinWith('content')
            ->where(['session.deleted_at' => null]);

        if ($onlyEnabled) {
            $query->andWhere(['session.enabled' => true]);
        }
        // Yii::error("Query: " . $query->createCommand()->getRawSql(), 'bbb');
        return $query->all();
    }

    /** Holt exakt eine Session (oder null) – optional Container-Check */
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

    /** Prüft, ob ein Raum bereits auf BBB läuft */
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


    /** Startet eine neue Session (oder idempotent) und liefert Moderator-URL */
    public function start(Session $s, ContentContainerActiveRecord $container = null): string
    {
        $url = $container ? $container->createUrl('/bbb/sessions') :
            Url::to('/bbb/sessions');
        $p = (new CreateMeetingParameters($s->uuid, $s->name))
            ->setModeratorPassword($s->moderator_pw)
            ->setAttendeePassword($s->attendee_pw)
            ->setAllowStartStopRecording(true)
            ->setWelcomeMessage($s->description ?? '')
            ->setLogoutUrl(Yii::$app->urlManager->createAbsoluteUrl($url . "?highlight=" . $s->id));

        $r = $this->bbb->createMeeting($p);          // mehrfach aufrufbar
        return $this->joinUrl($s, true);
    }

    /** Baut eine Join-URL für den gegebenen Nutzer */
    public function joinUrl(Session $session, bool $moderator = false): string
    {
        $jp = (new JoinMeetingParameters())
            ->setUserName(Yii::$app->user->identity->displayName)
            ->setPassword(
                $moderator ? $session->moderator_pw : $session->attendee_pw
            )
            ->setMeetingId($session->uuid)
            ->setUserId(Yii::$app->user->identity->email)
            ->setAvatarURL(avatarURL: Url::to(Yii::$app->user->identity->getProfileImage()->getUrl(), true));
        return $this->bbb->getJoinMeetingURL($jp);
    }
}
