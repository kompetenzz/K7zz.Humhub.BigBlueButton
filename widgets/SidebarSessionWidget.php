<?php

namespace k7zz\humhub\bbb\widgets;

use humhub\components\Widget;
use humhub\modules\content\components\ContentContainerActiveRecord;
use k7zz\humhub\bbb\models\Session;
use k7zz\humhub\bbb\services\SessionService;
use Yii;

/**
 * Widget: BBB sessions in the space right-column sidebar.
 *
 * Renders all sessions with show_in_sidebar=1 for the given container,
 * filtered by the current user's permissions.
 */
class SidebarSessionWidget extends Widget
{
    public ?ContentContainerActiveRecord $contentContainer = null;

    public function run(): string
    {
        $query = Session::find()
            ->alias('session')
            ->joinWith('content')
            ->where(['session.deleted_at' => null, 'session.show_in_sidebar' => 1])
            ->orderBy(['session.is_space_default' => SORT_DESC, 'session.title' => SORT_ASC]);

        if ($this->contentContainer !== null) {
            $query->contentContainer($this->contentContainer);
        } else {
            $query->andWhere(['content.contentcontainer_id' => null]);
        }

        $sessions = $query->all();

        $sessions = array_values(array_filter(
            $sessions,
            fn($s) => $s->canJoin() || $s->canStart()
        ));


        if (empty($sessions)) {
            return '';
        }

        try {
            $svc = new SessionService();
            $rows = array_map(fn($s) => [
                'model' => $s,
                'running' => $svc->isRunning($s->uuid),
            ], $sessions);
        } catch (\Throwable $e) {
            Yii::error($e, 'bbb');
            $rows = array_map(fn($s) => ['model' => $s, 'running' => false], $sessions);
        }

        return $this->render('sidebarSessionWidget', [
            'rows' => $rows,
            'contentContainer' => $this->contentContainer,
        ]);
    }
}
