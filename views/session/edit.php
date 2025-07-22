<?php
/**
 * Formular zum Anlegen / Bearbeiten einer BBB-Session
 *
 * @var k7zz\humhub\bbb\models\forms\SessionForm $model
 */

use humhub\modules\user\widgets\UserPickerField;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
$spaceTitle = $this->context->contentContainer
    ? $this->context->contentContainer->getDisplayName() . ": "
    : "";
$cancelUrl = $this->context->contentContainer
    ? $this->context->contentContainer->createUrl('/bbb/sessions')
    : Url::to('/bbb/sessions');

$title = $spaceTitle . ($model->id
    ? Yii::t('BbbModule.base', 'Edit session')
    : Yii::t('BbbModule.base', 'Create session'));
?>
<div class="content">
    <div id="layout-content">
        <div class="container-fluid">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h1><?= Html::encode($title) ?></h1>
                </div>

                <?php $f = ActiveForm::begin([
                    'id' => 'bbb-session-form',
                    'enableClientValidation' => true,
                    'enableAjaxValidation' => false,
                    'options' => ['enctype' => 'multipart/form-data'],
                    'fieldConfig' => [
                        'template' => "{label}\n{input}\n{hint}\n{error}",
                        'labelOptions' => ['class' => 'control-label'],
                        'inputOptions' => ['class' => 'form-control'],
                    ],
                ]); ?>

                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <?= $f->field($model, 'title')
                                    ->textInput(['maxlength' => true])
                                    ->hint(Yii::t(
                                        'BbbModule.base',
                                        'Speaking name for your audience, e.g. "Weekly Team Meeting"'
                                    )); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <?= $f->field($model, 'name')
                                    ->textInput(['maxlength' => true])
                                    ->hint(Yii::t(
                                        'BbbModule.base',
                                        'Used as identifier and for the URL of the session, e.g. "weekly-team-meeting"'
                                    )); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <?= $f->field($model, 'description')
                                    ->textarea(['rows' => 6])
                                    ->hint(Yii::t(
                                        'BbbModule.base',
                                        'Optional detailed description of the session and it\'s purpose.'
                                    )); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <?= $f->field($model, 'moderator_pw')
                                    ->textInput(['maxlength' => true])
                                    ->hint(Yii::t(
                                        'BbbModule.base',
                                        'Use this safe suggestion.'
                                    )); ?>
                            </div>
                            <div class="form-group">

                                <?= $f->field($model, 'attendee_pw')
                                    ->textInput(['maxlength' => true])
                                    ->hint(Yii::t(
                                        'BbbModule.base',
                                        'Use this safe suggestion.'
                                    )); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <?= $f->field($model, 'publicJoin')->checkbox([
                                    'id' => 'public-join-toggle',
                                    'label' => Yii::t('BbbModule.base', 'Allow everybody with permission to join this session'),
                                ]); ?>
                            </div>
                            <div class="form-group">
                                <div id="user-picker" <?= $model->publicJoin ? 'style="display:none"' : '' ?>>
                                    <?= $f->field($model, 'attendeeRefs')
                                        ->widget(class: UserPickerField::class)
                                        ->label(
                                            Yii::t('BbbModule.base', 'Select specific users for this session')
                                        );
                                    ; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <?= $f->field($model, 'joinCanStart')->checkbox([
                                    'label' => Yii::t('BbbModule.base', 'Allow everybody with join permission to start this session'),
                                ]); ?>
                            </div>
                            <div class="form-group">
                                <?= $f->field($model, 'joinCanModerate')->checkbox([
                                    'label' => Yii::t('BbbModule.base', 'Allow everybody with join permission to moderate this session'),
                                ]); ?>
                            </div>
                            <div class="form-group">
                                <?= $f->field($model, 'publicModerate')
                                    ->checkbox([
                                        'id' => 'public-moderate-toggle',
                                        'label' => Yii::t('BbbModule.base', 'Allow everybody with permission to moderate this session'),
                                    ]); ?>
                            </div>
                            <div class="form-group">
                                <div id="moderator-picker" <?= $model->publicModerate ? 'style="display:none"' : '' ?>>
                                    <?= $f->field($model, 'moderatorRefs')
                                        ->widget(class: UserPickerField::class)
                                        ->label(
                                            Yii::t('BbbModule.base', 'Select specific moderators for this session')
                                        );
                                    ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <?= $f->field($model, 'hasWaitingRoom')->checkbox([
                                    'label' => Yii::t('BbbModule.base', 'Join users via waiting room')
                                ]); ?>
                            </div>
                            <div class="form-group">
                                <?= $f->field($model, 'allowRecording')->checkbox([
                                    'label' => Yii::t('BbbModule.base', 'Allow recording this session'),
                                ]); ?>
                            </div>
                            <div class="form-group">
                                <?= $f->field($model, 'muteOnEntry')->checkbox([
                                    'label' => Yii::t('BbbModule.base', 'Mute users on session entry'),
                                ]); ?>
                            </div>

                        </div>
                        <div class="col-md-6">
                            <?php
                            if ($model->previewImage !== null) { ?>
                                <img src="<?= $model->previewImage->getUrl() ?>" class="img-responsive img-thumbnail"
                                    alt="<?= Yii::t('BbbModule.base', 'Session image') ?>"
                                    style="max-height: 200px; max-width: 100%; margin-bottom: 10px;">
                            <?php } ?>
                            <div class="form-group">
                                <?= $f->field($model, 'image')
                                    ->fileInput()
                                    ->label($model->previewImage
                                        ? Yii::t('BbbModule.base', 'Change session image')
                                        : Yii::t('BbbModule.base', 'Upload session image'))
                                    ->hint(Yii::t(
                                        'BbbModule.base',
                                        'Optional image for this session. Recommended size: 800x600px.'
                                    )); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel-footer">
                    <?= Html::submitButton(
                        Yii::t('BbbModule.base', 'Save'),
                        ['class' => 'btn btn-primary']
                    ); ?>
                    <?= Html::a(
                        Yii::t('BbbModule.base', 'Cancel'),
                        [$cancelUrl],
                        ['class' => 'btn btn-default']
                    ); ?>
                </div>

                <?php ActiveForm::end(); ?>

                <?php
                /* JS, um den User-Picker live ein- und auszublenden */
                $this->registerJs("
        $('#public-join-toggle').on('change', function() {
            $('#user-picker').toggle(!this.checked);
        });
        $('#public-moderate-toggle').on('change', function() {
            $('#moderator-picker').toggle(!this.checked);
        });");
                ?>
            </div>

        </div>
    </div>
</div>
<script type="module">
    import { SlugHelper } from '/path/to/SlugHelper.js';

    new SlugHelper({
        titleSelector: '#title',
        slugSelector: '#name',
        autogenerate: true // false = nur manuell
    });
</script>