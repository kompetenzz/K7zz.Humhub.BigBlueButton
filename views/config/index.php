<?php
/**
 * View: Global BBB module settings form.
 *
 * @var k7zz\humhub\bbb\models\forms\SettingsForm $model  The settings form model
 */

use humhub\modules\ui\form\widgets\ActiveForm;
use yii\helpers\Url;
$url = Url::to('/bbb/sessions');
?>

<div class="card">
    <div class="card-header"><?= Yii::t('BbbModule.config', '<strong>Bigbluebutton</strong> Integration'); ?></div>

    <div class="card-body">
        <div class="clearfix">
            <h4><?= Yii::t('BbbModule.config', 'Settings') ?></h4>
            <div class="help-block">
                <?= Yii::t('BbbModule.config', 'On this page you can configure general settings of your Bigbluebutton integration.') ?>
            </div>
        </div>

        <hr>

        <?php $form = ActiveForm::begin() ?>
        <div class="card-body">
            <h3></h3><?= Yii::t('BbbModule.config', 'API Settings') ?></h3>
            <?= $form->field($model, 'bbbUrl')
                ->textInput(options: ['placeholder' => 'https://bbb.example.org/bigbluebutton/']); ?>

            <?= $form->field($model, 'bbbSecret')
                ->passwordInput(['autocomplete' => 'new-password']); ?>

            <h3><?= Yii::t('BbbModule.config', 'Navigation') ?></h3>
            <p><?= Yii::t('BbbModule.config', 'You can always access the global sessions setup screen at') ?>
                <a href="<?= $url ?>"><?= $url ?></a>.
            </p>
            <?= $form->field($model, 'addNavItem')
                ->checkbox(); ?>

            <?= $form->field($model, 'navItemLabel')
                ->textInput() ?>
        </div>

        <button class="btn btn-primary" data-ui-loader><?= Yii::t('base', 'Save') ?></button>

        <?php ActiveForm::end() ?>
    </div>



</div>