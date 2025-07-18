<?php
namespace k7zz\humhub\bbb\widgets;
use humhub\components\Widget;
use k7zz\humhub\bbb\models\Session;

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
            'model' => $this->session,
            'running' => $this->running,
            'scope' => $this->scope,
            'contentContainer' => $this->contentContainer,
            'highlightId' => $this->highlightId ?? 0,
        ]);
    }
}
