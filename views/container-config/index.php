<?php
/**
 * View: Container-specific BBB module settings form.
 *
 * @var k7zz\humhub\bbb\models\forms\ContainerSettingsForm $model  The container settings form model
 */

use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\libs\Html;
use humhub\modules\ui\icon\widgets\Icon;

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

            <?= $form->field($model, 'sidebarSortOrder')
                ->textInput(['type' => 'number', 'min' => 1, 'max' => 9000])
                ->hint(Yii::t('BbbModule.config', 'Position of the BBB widget in the right sidebar. Lower = higher up. Reference: Activities = 10, Members = 30. Default = 1 (top), unsorted widgets = 9000.')) ?>
        </div>

        <button class="btn btn-primary" data-ui-loader><?= Yii::t('base', 'Save') ?></button>

        <?php ActiveForm::end() ?>

        <hr>

        <h4><?= Yii::t('BbbModule.config', 'Default Session') ?></h4>
        <p class="help-block">
            <?= Yii::t('BbbModule.config', 'Creates a default BBB session for this space with the title "{title}" and shows it in the sidebar.', [
                'title' => $this->context->contentContainer->getDisplayName() . ' (Default-Session)',
            ]) ?>
        </p>
        <?= Html::a(
            Icon::get('plus') . ' ' . Yii::t('BbbModule.config', 'Create default session'),
            $this->context->contentContainer->createUrl('/bbb/container-config/create-default-session'),
            [
                'class' => 'btn btn-secondary',
                'data-method' => 'post',
                'data-confirm' => Yii::t('BbbModule.config', 'Create a default BBB session for this space?'),
            ]
        ) ?>
    </div>



</div>