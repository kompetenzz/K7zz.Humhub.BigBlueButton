<?php
/**
 * View: Container-specific BBB module settings form.
 *
 * @var k7zz\humhub\bbb\models\forms\ContainerSettingsForm $model  The container settings form model
 */

use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\libs\Html;

/* @var $this \humhub\modules\ui\view\components\View *
/* @var $subNav string */
/* @var $model \humhub\modules\custom_pages\models\forms\SettingsForm */

$url = $this->context->contentContainer->createUrl('/bbb/sessions');

?>

<div class="card">
    <div class="card-header"><?= Yii::t('BbbModule.config', '<strong>Bigbluebutton</strong> Integration'); ?></div>

    <div class="card-body">
        <div class="clearfix">
            <h4><?= Yii::t('BbbModule.config', 'Settings') ?></h4>
            <div class="help-block">
                <?= Yii::t('BbbModule.config', 'On this page you can configure container settings of your Bigbluebutton integration.') ?>
            </div>
        </div>

        <hr>

        <?php $form = ActiveForm::begin() ?>
        <div class="card-body">
            <p><?= Yii::t('BbbModule.config', 'You can always access the container sessions setup screen at') ?>
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