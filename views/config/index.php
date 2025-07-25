<?php
/**
 * View: Global BBB module settings form.
 *
 * @var k7zz\humhub\bbb\models\forms\SettingsForm $model  The settings form model
 */

use yii\bootstrap\ActiveForm;

?>

<div class="panel panel-default">
    <div class="panel-heading"><?= Yii::t('BbbModule.config', '<strong>Bigbluebutton</strong> Integration'); ?></div>

    <div class="panel-body">
        <div class="clearfix">
            <h4><?= Yii::t('BbbModule.config', 'Settings') ?></h4>
            <div class="help-block">
                <?= Yii::t('BbbModule.config', 'On this page you can configure general settings of your Bigbluebutton integration.') ?>
            </div>
        </div>

        <hr>

        <?php $form = ActiveForm::begin() ?>
        <div class="panel-body">
            <?= $form->field($model, 'bbbUrl')
                ->textInput(options: ['placeholder' => 'https://bbb.example.org/bigbluebutton/']); ?>

            <?= $form->field($model, 'bbbSecret')
                ->passwordInput(['autocomplete' => 'new-password']); ?>

            <?= $form->field($model, 'addNavItem')
                ->checkbox(); ?>

            <?= $form->field($model, 'navItemLabel')
                ->textInput() ?>
        </div>

        <button class="btn btn-primary" data-ui-loader><?= Yii::t('base', 'Save') ?></button>

        <?php ActiveForm::end() ?>
    </div>



</div>