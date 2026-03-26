<?php

namespace k7zz\humhub\bbb\controllers;

use humhub\modules\content\components\ContentContainerController;
use humhub\modules\content\components\ContentContainerControllerAccess;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use k7zz\humhub\bbb\models\forms\ContainerSettingsForm;
use k7zz\humhub\bbb\models\forms\SessionForm;
use Yii;

/**
 * Controller for managing BBB settings per content container (e.g. Space or User).
 *
 * Only admins or the container owner can access these settings.
 */
class ContainerConfigController extends ContentContainerController
{
    /**
     * @inheritdoc
     * Restricts access to admins and the container owner.
     */
    protected function getAccessRules()
    {
        return [[ContentContainerControllerAccess::RULE_USER_GROUP_ONLY => [Space::USERGROUP_ADMIN, User::USERGROUP_SELF]]];
    }

    /**
     * Displays and processes the container-specific settings form for the BBB module.
     * @return string
     */
    public function actionIndex()
    {
        $model = new ContainerSettingsForm(['contentContainer' => $this->contentContainer]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->view->saved();
        }

        return $this->render('index', [
            'model' => $model,
        ]);
    }

    /**
     * Creates a default BBB session for this container if it does not exist yet.
     *
     * Title: "<SpaceName> (Default-Session)", show_in_sidebar = true.
     */
    public function actionCreateDefaultSession()
    {
        $container = $this->contentContainer;
        $title = $container->getDisplayName() . ' (Default-Session)';

        $form = SessionForm::create($container);
        $form->title = $title;
        $form->showInSidebar = true;

        if ($form->save()) {
            $this->view->saved();
        } else {
            Yii::error('Could not create default BBB session: ' . json_encode($form->getErrors()), 'bbb');
            $this->view->error(Yii::t('BbbModule.base', 'Could not create default session.'));
        }

        return $this->redirect($container->createUrl('/bbb/container-config'));
    }
}
