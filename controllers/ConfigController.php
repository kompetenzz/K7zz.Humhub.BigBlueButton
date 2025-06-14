<?php

namespace humhub\modules\bbb\controllers;

use Yii;
use humhub\modules\admin\components\Controller;
use humhub\modules\bbb\models\forms\SettingsForm;

class ConfigController extends Controller
{
    /**
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
