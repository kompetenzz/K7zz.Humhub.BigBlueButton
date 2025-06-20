<?php

namespace k7zz\humhub\bbb\controllers;

use k7zz\humhub\bbb\controllers\BaseContentController;
use Yii;

class SessionsController extends BaseContentController
{
    public function actionIndex(?int $highlight = 0)
    {
        $sessions = array_filter(
            $this->svc->list($this->contentContainer?->id),
            fn($s) => $s->canJoin() ||
            $s->canStart()
        );

        $rows = array_map(
            fn($s) => [
                'model' => $s,
                'running' => $this->svc->isRunning($s->uuid)
            ],
            $sessions
        );

        return $this->render(
            '@bbb/views/sessions/index',
            [
                'rows' => $rows,
                'scope' => $this->scope,
                'highlightId' => $highlight
            ]
        );
    }
}
