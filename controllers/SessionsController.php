<?php

namespace k7zz\humhub\bbb\controllers;

use k7zz\humhub\bbb\controllers\BaseContentController;
use Yii;

/**
 * Controller for listing all BBB sessions in a content container (space or user).
 *
 * Shows only sessions the user can join or start.
 */
class SessionsController extends BaseContentController
{
    /**
     * Lists all sessions for the current content container, filtered by permissions.
     * @param int|null $highlight ID to highlight in the view
     * @return string
     */
    public function actionIndex(?int $highlight = 0)
    {
        $sessions = array_filter(
            $this->svc->list($this->contentContainer),
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
                'highlightId' => $highlight
            ]
        );
    }
}
