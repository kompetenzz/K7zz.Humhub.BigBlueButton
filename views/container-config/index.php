<?php
/**
 * View: Container-specific BBB module settings form.
 *
 * @var k7zz\humhub\bbb\models\forms\ContainerSettingsForm $model  The container settings form model
 */

use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\modules\ui\icon\widgets\Icon;
use humhub\modules\user\models\User;

/* @var $this \humhub\modules\ui\view\components\View *
/* @var $subNav string */
/* @var $model \humhub\modules\custom_pages\models\forms\SettingsForm */

$container = $this->context->contentContainer;
$url = $container->createUrl('/bbb/sessions');
$isUserProfile = $container instanceof User;

?>

<div class="card">
    <div class="card-header d-flex align-items-center">
        <span class="me-auto"><?= Yii::t('BbbModule.config', '<strong>Bigbluebutton</strong> Integration') ?></span>
        <a href="<?= $url ?>" class="btn btn-sm btn-outline-primary">
            <?= Icon::get('video-camera') ?> <?= Yii::t('BbbModule.config', 'Go to sessions') ?>
        </a>
    </div>

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

            <?= $form->field($model, 'addNavItem')
                ->checkbox(); ?>

            <div id="bbb-nav-admin-hint" class="alert alert-info"
                style="display: <?= $model->addNavItem ? 'none' : '' ?>;">
                <?= $isUserProfile
                    ? Yii::t('BbbModule.config', 'When the navigation entry is disabled, you will still find a <strong>Video-Sessions</strong> link in the account menu.')
                    : Yii::t('BbbModule.config', 'When the navigation entry is disabled, administrators will still find a <strong>Sessions</strong> link in the space gear menu.')
                    ?>
            </div>

            <?= $form->field($model, 'navItemLabel')
                ->textInput() ?>

            <?= $form->field($model, 'sidebarSortOrder')
                ->textInput(['type' => 'number', 'min' => 1, 'max' => 9000])
                ->hint(Yii::t('BbbModule.config', 'Position of the BBB widget in the right sidebar. Lower = higher up. Reference: Activities = 10, Members = 30. Default = 1 (top), unsorted widgets = 9000.')) ?>
        </div>

        <button class="btn btn-primary" data-ui-loader><?= Yii::t('base', 'Save') ?></button>

        <?php ActiveForm::end() ?>

        <script>
            document.getElementById('containersettingsform-addnavitem').addEventListener('change', function () {
                document.getElementById('bbb-nav-admin-hint').style.display = this.checked ? 'none' : '';
            });
        </script>

    </div>



</div>