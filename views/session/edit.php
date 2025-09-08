<?php
/**
 * Formular zum Anlegen / Bearbeiten einer BBB-Session
 *
 * @var k7zz\humhub\bbb\models\forms\SessionForm $model
 */

use humhub\modules\ui\form\widgets\ContentHiddenCheckbox;
use humhub\modules\ui\form\widgets\ContentVisibilitySelect;
use humhub\modules\user\widgets\UserPickerField;
use humhub\modules\content\widgets\richtext\RichTextField;
use k7zz\humhub\bbb\models\forms\SessionForm;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use k7zz\humhub\bbb\assets\BBBAssets;
use k7zz\humhub\bbb\enums\Layouts;

$bundle = BBBAssets::register($this);

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
                                    ->textInput([
                                        'maxlength' => true,
                                        'style' => 'text-transform: lowercase;',
                                        'pattern' => SessionForm::SLUG_PATTERN,
                                        'data-slugify' => 'true',
                                        'data-slugify-title-selector' => '#sessionform-title',
                                        'data-slugify-autogenerate' => 'true'
                                    ])
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
                                    ->widget(RichtextField::class, ['placeholder' => Yii::t('BbbModule.base', 'Please describe...')])
                                    ->hint(Yii::t(
                                        'BbbModule.base',
                                        'Optional detailed description of the session and it\'s purpose.'
                                    )); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <?= $f->field($model, 'layout')->radioList(
                                    Layouts::options(),
                                    [
                                        'item' => function ($index, $label, $name, $checked, $value) {
                                        $desc = Layouts::descriptions()[$value] ?? '';
                                        return "
                                            <div class='radio'>
                                                <label>
                                                    <input type='radio' name='$name' value='$value' " . ($checked ? 'checked' : '') . ">
                                                        <strong>$label</strong><br>
                                                        <small class='text-muted'>$desc</small>
                                                </label>
                                            </div>
                                        ";
                                    }
                                    ]
                                ); ?>
                            </div>

                            <?php /** Passwords not used anymore

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
*/ ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <?= $f->field($model, 'visibility')->widget(ContentVisibilitySelect::class) ?>
                            <?php if (!$model->id) {
                                echo $f->field($model, 'hidden')->widget(ContentHiddenCheckbox::class);
                            } ?>
                            <div class="form-group" id="public-join-box">
                                <?= $f->field($model, 'publicJoin')->checkbox([
                                    'id' => 'public-join-toggle',
                                    'label' => Yii::t('BbbModule.base', 'Allow public joining by a shareable link.'),
                                ])->hint(Yii::t(
                                            'BbbModule.base',
                                            'Creates a public join link which can be used by anybody to join this session (no login required).'
                                        )); ?>
                            </div>
                            <div class="form-group" id="join-by-permissions-box">
                                <?= $f->field($model, 'joinByPermissions')->checkbox([
                                    'id' => 'join-by-permissions-toggle',
                                    'label' => Yii::t('BbbModule.base', 'Join by humhub permissions.'),
                                ])->hint(Yii::t(
                                            'BbbModule.base',
                                            'Allow everybody with access by humhub settings to join this session. Uncheck to select specific users below.'
                                        )); ?>
                            </div>
                            <div class="form-group" id="user-picker-box" <?= $model->joinByPermissions ? 'style="display:none"' : '' ?>>
                                <div id="user-picker">
                                    <?= $f->field($model, 'attendeeRefs')
                                        ->widget(class: UserPickerField::class)
                                        ->label(
                                            Yii::t('BbbModule.base', 'Select specific attendees for this session.')
                                        );
                                    ; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <?= $f->field($model, 'joinCanStart')->checkbox([
                                    'label' => Yii::t('BbbModule.base', 'Join can start'),
                                ])
                                    ->hint(Yii::t(
                                        'BbbModule.base',
                                        'Allow everybody with join permission to start this session.'
                                    )); ?>
                            </div>
                            <div class="form-group">
                                <?= $f->field($model, 'joinCanModerate')->checkbox([
                                    'label' => Yii::t('BbbModule.base', 'Join can moderate'),
                                ])->hint(Yii::t(
                                            'BbbModule.base',
                                            'Allow everybody with join permission to moderate this session.'
                                        )); ?>
                            </div>
                            <div class="form-group" id="moderator-box">
                                <?= $f->field($model, 'moderateByPermissions')
                                    ->checkbox(options: [
                                        'id' => 'moderate-by-permissions-toggle',
                                        'label' => Yii::t('BbbModule.base', 'Moderate by humhub permissions'),
                                    ])->hint(Yii::t(
                                            'BbbModule.base',
                                            'Allow everybody with manage access by humhub settings to moderate this session. Uncheck to select specific users below.'
                                        )); ?>
                            </div>
                            <div class="form-group" id="moderator-picker-box" <?= $model->moderateByPermissions ? 'style="display:none"' : '' ?>>
                                <div id="moderator-picker">
                                    <?= $f->field($model, 'moderatorRefs')
                                        ->widget(class: UserPickerField::class)
                                        ->label(
                                            Yii::t('BbbModule.base', 'Select specific moderators for this session.')
                                        );
                                    ?>
                                </div>
                            </div>

                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <?= $f->field($model, 'hasWaitingRoom')->checkbox([
                                    'label' => Yii::t('BbbModule.base', 'Waiting room'),
                                ])->hint(Yii::t(
                                            'BbbModule.base',
                                            'Join users via a waiting room and let a moderator accept them.'
                                        )); ?>
                            </div>
                            <div class="form-group">
                                <?= $f->field($model, 'allowRecording')->checkbox([
                                    'label' => Yii::t('BbbModule.base', 'Allow recording this session.'),
                                ])->hint(Yii::t(
                                            'BbbModule.base',
                                            'Recordings must be started manually.'
                                        )); ?>
                            </div>
                            <div class="form-group">
                                <?= $f->field($model, 'muteOnEntry')->checkbox([
                                    'label' => Yii::t('BbbModule.base', 'Mute on entry'),
                                ])->hint(Yii::t(
                                            'BbbModule.base',
                                            'All users will be muted when entering the session.'
                                        )); ?>
                            </div>
                            <?php
                            if ($model->presentationFile !== null) { ?>
                                <label><?= Yii::t('BbbModule.base', 'Current presentation') ?></label>
                                <?php if ($model->presentationPreviewImage !== null) { ?>
                                    <img src="<?= $model->presentationPreviewImage->getUrl() ?>"
                                        class="img-responsive img-thumbnail"
                                        alt="<?= Yii::t('BbbModule.base', 'PDF preview') ?>"
                                        style="max-height: 200px; max-width: 100%; margin-bottom: 10px;">
                                <?php } ?>
                                <span><?= $model->presentationFile->file_name ?>
                                    (<?= round($model->presentationFile->size / 1024 / 1024, 2); ?>MB)</span>
                            <?php } ?>
                            <div class="form-group">
                                <?= $f->field($model, 'presentationUpload')
                                    ->fileInput()
                                    ->label($model->presentationFile
                                        ? Yii::t('BbbModule.base', 'Change presentation file.')
                                        : Yii::t('BbbModule.base', 'Upload presentation file.'))
                                    ->hint(Yii::t(
                                        'BbbModule.base',
                                        'Optional presentation for this session. Use pdf in landscape mode.'
                                    )); ?>
                            </div>
                            <?php
                            if ($model->previewImage !== null) { ?>
                                <img src="<?= $model->previewImage->getUrl() ?>" class="img-responsive img-thumbnail"
                                    alt="<?= Yii::t('BbbModule.base', 'Session image') ?>"
                                    style="max-height: 200px; max-width: 100%; margin-bottom: 10px;">
                            <?php } ?>
                            <div class="form-group">
                                <?= $f->field($model, 'imageUpload')
                                    ->fileInput()
                                    ->label($model->previewImage
                                        ? Yii::t('BbbModule.base', 'Change session image.')
                                        : Yii::t('BbbModule.base', 'Upload session image.'))
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
                    function toggleControls() {
                        $('#user-picker-box').toggle(!$('#join-by-permissions-toggle').is(':checked'));
                        $('#moderator-picker-box').toggle(!$('#moderate-by-permissions-toggle').is(':checked'));
                    }

                    $(document).ready(() => {
                        $('#join-by-permissions-toggle, #moderate-by-permissions-toggle')
                            .on('change', () => toggleControls());

                        toggleControls(); // initial call
                    });
                ");
                ?>

            </div>

        </div>
    </div>
</div>