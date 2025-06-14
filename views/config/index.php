<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use yii\bootstrap\ActiveForm;
use humhub\libs\Html;

/* @var $this \humhub\modules\ui\view\components\View *
/* @var $subNav string */
/* @var $model \humhub\modules\custom_pages\models\forms\SettingsForm */

?>

<div class="panel panel-default">
    <div class="panel-heading"><?= Yii::t('BbbModule.base', '<strong>Bigbluebutton</strong> Integration'); ?></div>

    <div class="panel-body">
        <div class="clearfix">
            <h4><?= Yii::t('BbbModule.base', 'Settings') ?></h4>
            <div class="help-block">
                <?= Yii::t('BbbModule.base', 'On this page you can configure general settings of your Bigbluebutton integration.') ?>
            </div>
        </div>

        <hr>

        <?php $form = ActiveForm::begin() ?>
        <div class="panel-body">
            <?= $form->field($model, 'bbbUrl')
                ->textInput(['placeholder' => 'https://bbb.example.org/bigbluebutton/']); ?>

            <?= $form->field($model, 'bbbSecret')
                ->passwordInput(['autocomplete' => 'new-password']); ?>
        </div>

        <button class="btn btn-primary" data-ui-loader><?= Yii::t('base', 'Save') ?></button>

        <?php ActiveForm::end() ?>
    </div>



</div>