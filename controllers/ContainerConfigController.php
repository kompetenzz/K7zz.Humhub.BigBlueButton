<?php

namespace k7zz\humhub\bbb\controllers;

use humhub\modules\content\components\ContentContainerController;
use humhub\modules\content\components\ContentContainerControllerAccess;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use k7zz\humhub\bbb\models\forms\ContainerSettingsForm;
use Yii;

class ContainerConfigController extends ContentContainerController
{
    /**
     * @inheritdoc
     */
    protected function getAccessRules()
    {
        return [[ContentContainerControllerAccess::RULE_USER_GROUP_ONLY => [Space::USERGROUP_ADMIN, User::USERGROUP_SELF]]];
    }

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
}
