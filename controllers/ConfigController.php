<?php

namespace k7zz\humhub\bbb\controllers;

use Yii;
use humhub\modules\admin\components\Controller;
use k7zz\humhub\bbb\models\forms\SettingsForm;

/**
 * Controller for managing global BBB module settings in the admin area.
 *
 * Provides a form for configuring the BBB server URL and secret.
 */
class ConfigController extends Controller
{
    /**
     * Displays and processes the global settings form for the BBB module.
     * @return string
     * @throws \Exception
     */
    public function actionIndex()
    {
        $model = new SettingsForm();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->view->saved();
        }

        return $this->render('index', [
            'model' => $model,
        ]);
    }
}
