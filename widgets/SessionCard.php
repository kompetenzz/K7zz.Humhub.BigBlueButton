<?php
namespace k7zz\humhub\bbb\widgets;
use humhub\components\Widget;
use k7zz\humhub\bbb\models\Session;
use yii\helpers\Url;

/**
 * Widget: BBB session card.
 *
 * Renders a single session as a card element, including status and actions.
 *
 * @property Session $session              The session model
 * @property bool $running                 Whether the session is currently running
 * @property string $scope                 'container' or 'global'
 * @property int|null $highlightId         ID of the session to highlight
 * @property mixed $contentContainer       The content container (space/user) or null
 */
class SessionCard extends Widget
{
    public Session $session;
    public bool $running;
    public string $scope;   // 'container' | 'global'
    public ?int $highlightId = null;
    public $contentContainer = null;

    public function run()
    {
        return $this->render('sessionCard', [
            'session' => $this->session,
            'running' => $this->running,
            'contentContainer' => $this->contentContainer,
            'highlightId' => $this->highlightId ?? 0,
            'isRunningUrl' => $this->contentContainer
                ? $this->contentContainer->createUrl('/bbb/session/is-running', ['id' => $this->session->id])
                : Url::to(['/bbb/session/is-running', 'id' => $this->session->id]),
        ]);
    }
}
