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

    /* ------------------------------------------------------------------ */
    /*  API-Methoden                                                      */
    /* ------------------------------------------------------------------ */

    /** Liste aller Sessions – optional nach ContentContainer gefiltert */
    public function list(?int $containerId = null, bool $onlyEnabled = false): array
    {
        $c = [
            'deleted_at' => null,
            'contentcontainer_id' => $containerId
        ];
        if ($onlyEnabled) {
            $c['enabled'] = true; // nur aktive Sessions
        }
        $q = Session::find()
            ->where($c)
            ->orderBy(['ord' => SORT_ASC, 'title' => SORT_ASC]);

        return $q->all();
    }

    /** Holt exakt eine Session (oder null) – optional Container-Check */
    public function get(?int $id = null, ?int $containerId = null): ?Session
    {
        $c = [
            'deleted_at' => null, // nur nicht gelöschte Sessions
        ];
        if ($containerId !== null) {
            $c['contentcontainer_id'] = $containerId; // optional Container-Filter
        }
        if ($id !== null) {
            $c['id'] = $id; // Suche nach ID
        } else {
            return null; // Keine ID oder Slug angegeben
        }
        return Session::findOne($c);
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
    public function start(Session $s, $container = null): string
    {
        $p = new CreateMeetingParameters($s->uuid, $s->name);
        $p->setModeratorPassword($s->moderator_pw);
        $p->setAttendeePassword($s->attendee_pw);
        $p->setAllowStartStopRecording(true);
        $p->setWelcomeMessage($s->description ?? '');
        $url = $container ? $container->createUrl('/bbb/sessions') :
            '/bbb/sessions';
        $p->setLogoutUrl(Yii::$app->urlManager->createAbsoluteUrl($url . "?highlight=" . $s->id));

        $r = $this->bbb->createMeeting($p);          // mehrfach aufrufbar
        return $this->joinUrl($s, true);
    }

    /** Baut eine Join-URL für den gegebenen Nutzer */
    public function joinUrl(Session $session, bool $moderator = false): string
    {
        $jp = new JoinMeetingParameters(
            $session->uuid,
            Yii::$app->user->identity->displayName,
            $moderator ? $session->moderator_pw : $session->attendee_pw
        );
        $jp->setUserId(Yii::$app->user->identity->email);
        $jp->setAvatarURL(avatarURL: Yii::$app->user->identity->getProfileImage()->getUrl());
        return $this->bbb->getJoinMeetingURL($jp);
    }
}
