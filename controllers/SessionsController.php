<?php

namespace k7zz\humhub\bbb\controllers;

use k7zz\humhub\bbb\controllers\BaseContentController;
use k7zz\humhub\bbb\permissions\Admin;
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
     * Admins without a content container can switch to an "all sessions" grouped view.
     * @param int|null $highlight ID to highlight in the view
     * @param string   $view      'global' (default) or 'all'
     * @return string
     */
    public function actionIndex(?int $highlight = 0, string $view = 'global')
    {
        $isAdmin = Yii::$app->user->can(Admin::class);
        $showAll = ($view === 'all') && $isAdmin && !$this->contentContainer;

        if ($showAll) {
            $grouped = $this->svc->listAllGrouped();

            $mapRow = fn($s) => ['model' => $s, 'running' => $this->svc->isRunning($s->uuid)];

            $globalRows = array_map($mapRow, $grouped['global']);

            $spaceGroups = [];
            foreach ($grouped['spaces'] as $id => $group) {
                $spaceGroups[$id] = [
                    'container' => $group['container'],
                    'rows'      => array_map($mapRow, $group['sessions']),
                ];
            }

            $userGroups = [];
            foreach ($grouped['users'] as $id => $group) {
                $userGroups[$id] = [
                    'container' => $group['container'],
                    'rows'      => array_map($mapRow, $group['sessions']),
                ];
            }

            return $this->render('@bbb/views/sessions/index', [
                'rows'        => [],
                'highlightId' => $highlight,
                'viewMode'    => 'all',
                'isAdmin'     => $isAdmin,
                'globalRows'  => $globalRows,
                'spaceGroups' => $spaceGroups,
                'userGroups'  => $userGroups,
            ]);
        }

        $sessions = array_filter(
            $this->svc->list($this->contentContainer),
            fn($s) => $s->canJoin() || $s->canStart()
        );

        $rows = array_map(
            fn($s) => ['model' => $s, 'running' => $this->svc->isRunning($s->uuid)],
            $sessions
        );

        return $this->render('@bbb/views/sessions/index', [
            'rows'        => $rows,
            'highlightId' => $highlight,
            'viewMode'    => 'global',
            'isAdmin'     => $isAdmin,
            'globalRows'  => [],
            'spaceGroups' => [],
            'userGroups'  => [],
        ]);
    }
}
