<?php
/**
 * @var k7zz\humhub\bbb\models\forms\SessionForm $model
 */

use humhub\modules\ui\form\widgets\ContentHiddenCheckbox;
use humhub\modules\ui\form\widgets\ContentVisibilitySelect;
use humhub\modules\user\widgets\UserPickerField;
use humhub\modules\content\widgets\richtext\RichTextField;
use humhub\modules\topic\widgets\TopicPicker;
use k7zz\humhub\bbb\models\forms\SessionForm;
use k7zz\humhub\bbb\widgets\FilePreviewField;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use k7zz\humhub\bbb\assets\BBBAssets;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use k7zz\humhub\bbb\enums\Layouts;

BBBAssets::register($this);

$isUserProfile = $this->context->contentContainer instanceof User;

$spaceTitle = $this->context->contentContainer
    ? $this->context->contentContainer->getDisplayName() . ": "
    : "";
$cancelUrl = $this->context->contentContainer
    ? $this->context->contentContainer->createUrl('/bbb/sessions')
    : Url::to('/bbb/sessions');

$title = $spaceTitle . ($model->id
    ? Yii::t('BbbModule.base', 'Edit session') . " " . $model->title
    : Yii::t('BbbModule.base', 'Create session'));

$this->setPageTitle($title);
?>
<div class="content">
    <div id="layout-content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
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

                <div class="card-body">

                    <div class="card mb-4">
                        <div class="card-header">
                            <strong><?= Yii::t('BbbModule.base', 'Basic Information') ?></strong>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <?= $f->field($model, 'title')
                                            ->textInput(['maxlength' => true])
                                            ->hint(Yii::t('BbbModule.base', 'Speaking name for your audience, e.g. "Weekly Team Meeting"')); ?>
                                    </div>
                                    <div class="form-group">
                                        <?= $f->field($model, 'name')
                                            ->textInput([
                                                'maxlength' => true,
                                                'style' => 'text-transform: lowercase;',
                                                'pattern' => SessionForm::SLUG_PATTERN,
                                                'data-slugify' => 'true',
                                                'data-slugify-title-selector' => '#sessionform-title',
                                                'data-slugify-autogenerate' => 'true',
                                            ])
                                            ->hint(Yii::t('BbbModule.base', 'Used as identifier and for the URL of the session, e.g. "weekly-team-meeting"')); ?>
                                    </div>
                                    <div class="form-group">
                                        <?= $f->field($model, 'description')
                                            ->widget(RichtextField::class, [
                                                'placeholder' => Yii::t('BbbModule.base', 'Please describe...')
                                            ])
                                            ->hint(Yii::t('BbbModule.base', 'Optional detailed description of the session and it\'s purpose.')); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <?php if (!$isUserProfile): ?>
                                        <div class="form-group">
                                            <?= $f->field($model, 'topics')->widget(TopicPicker::class, [
                                                'contentContainer' => $this->context->contentContainer,
                                            ]); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?= FilePreviewField::widget([
                                        'form' => $f,
                                        'model' => $model,
                                        'attribute' => 'imageUpload',
                                        'removeAttr' => 'removeImage',
                                        'preview' => $model->previewImage,
                                        'label' => Yii::t('BbbModule.base', 'Upload session image.'),
                                        'changeLabel' => Yii::t('BbbModule.base', 'Change session image.'),
                                        'hint' => Yii::t('BbbModule.base', 'Optional image for this session. Recommended size: 800x600px.'),
                                    ]) ?>
                                    <?php if (Yii::$app->getModule('bbb')->settings->get('integrateBbbChat')): ?>
                                        <div class="form-group mt-3">
                                            <?= $f->field($model, 'integrateBbbChat')->checkbox([
                                                'label' => Yii::t('BbbModule.base', 'Enable session chat'),
                                            ])->hint(Yii::t('BbbModule.base', 'Adds a persistent chat to this session page. Messages can be written before, during and after meetings and are synchronised with BBB chat in real time.')); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <strong><?= Yii::t('BbbModule.base', 'Visibility and Notifications') ?></strong>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <?= $f->field($model, 'visibility')->widget(ContentVisibilitySelect::class, ['contentOwner' => 'record']) ?>
                                    <?= $f->field($model, 'hidden')->widget(ContentHiddenCheckbox::class); ?>
                                    <div class="form-group">
                                        <?= $f->field($model, 'notifyOnStart')->checkbox([
                                            'label' => Yii::t('BbbModule.base', 'Enable start notifications'),
                                        ])->hint(Yii::t('BbbModule.base', 'Notify invited users when this session is started. Users can manage their notification preferences individually in their HumHub account settings.')); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <?= $f->field($model, 'showInSidebar')->checkbox([
                                            'label' => Yii::t('BbbModule.base', 'Show in right column'),
                                        ])->hint(
                                                $this->context->contentContainer
                                                ? Yii::t('BbbModule.base', 'Displays this session in the right sidebar panel of the space.')
                                                : Yii::t('BbbModule.base', 'Displays this session in the right column of the main dashboard.')
                                            ); ?>
                                    </div>
                                    <div class="form-group">
                                        <?php
                                        $container = $this->context->contentContainer;
                                        $defaultLabel = $container instanceof Space
                                            ? Yii::t('BbbModule.base', 'Space default session')
                                            : ($container !== null
                                                ? Yii::t('BbbModule.base', 'Profile default session')
                                                : Yii::t('BbbModule.base', 'Default session'));
                                        $defaultHint = $container instanceof Space
                                            ? Yii::t('BbbModule.base', 'Marks this as the default session for this space. The title will be hidden in the sidebar.')
                                            : ($container !== null
                                                ? Yii::t('BbbModule.base', 'Marks this as the default session for this profile. The title will be hidden in the sidebar.')
                                                : Yii::t('BbbModule.base', 'Marks this as the default session. The title will be hidden in the sidebar.'));
                                        ?>
                                        <?= $f->field($model, 'isSpaceDefault')->checkbox([
                                            'label' => $defaultLabel,
                                        ])->hint($defaultHint); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <strong>
                                <?= Yii::t('BbbModule.base', 'Join & Moderation') ?>
                            </strong>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group" id="public-join-box">
                                        <?= $f->field($model, 'publicJoin')->checkbox([
                                            'id' => 'public-join-toggle',
                                            'label' => Yii::t('BbbModule.base', 'Allow public joining by a shareable link.'),
                                        ])->hint(Yii::t('BbbModule.base', 'Creates a public join link which can be used by anybody to join this session (no login required).')); ?>
                                    </div>
                                    <?php if (!$isUserProfile): ?>
                                        <div class="form-group" id="join-by-permissions-box">
                                            <?= $f->field($model, 'joinByPermissions')->checkbox([
                                                'id' => 'join-by-permissions-toggle',
                                                'label' => Yii::t('BbbModule.base', 'Join by humhub permissions.'),
                                            ])->hint(Yii::t('BbbModule.base', 'Allow everybody with access by humhub settings to join this session. Uncheck to select specific users below.')); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="form-group" id="user-picker-box">
                                        <?= $f->field($model, 'attendeeRefs')
                                            ->widget(UserPickerField::class)
                                            ->label(Yii::t('BbbModule.base', 'Select specific attendees for this session.')); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <?php if (!$isUserProfile): ?>
                                        <div class="form-group" id="moderator-box">
                                            <?= $f->field($model, 'moderateByPermissions')->checkbox([
                                                'id' => 'moderate-by-permissions-toggle',
                                                'label' => Yii::t('BbbModule.base', 'Moderate by humhub permissions'),
                                            ])->hint(Yii::t('BbbModule.base', 'Allow everybody with manage access by humhub settings to moderate this session. Uncheck to select specific users below.')); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="form-group" id="moderator-picker-box">
                                        <?= $f->field($model, 'moderatorRefs')
                                            ->widget(UserPickerField::class)
                                            ->label(Yii::t('BbbModule.base', 'Select specific moderators for this session.')); ?>
                                    </div>
                                    <div class="form-group">
                                        <?= $f->field($model, 'joinCanStart')->checkbox([
                                            'id' => 'bbb-joincanstart-toggle',
                                            'label' => Yii::t('BbbModule.base', 'Join can start'),
                                        ])->hint(Yii::t('BbbModule.base', 'Allow everybody with join permission to start this session.')); ?>
                                    </div>
                                    <div class="form-group">
                                        <?= $f->field($model, 'joinCanModerate')->checkbox([
                                            'id' => 'bbb-joincanmoderate-toggle',
                                            'label' => Yii::t('BbbModule.base', 'Join can moderate'),
                                        ])->hint(Yii::t('BbbModule.base', 'Allow everybody with join permission to moderate this session.')); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <strong><?= Yii::t('BbbModule.base', 'In-Session Options') ?></strong>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <?= $f->field($model, 'hasWaitingRoom')->checkbox([
                                            'id' => 'bbb-waitingroom-toggle',
                                            'label' => Yii::t('BbbModule.base', 'Waiting room'),
                                        ])->hint(Yii::t('BbbModule.base', 'Join users via a waiting room and let a moderator accept them.')); ?>
                                    </div>
                                    <div class="form-group">
                                        <?= $f->field($model, 'muteOnEntry')->checkbox([
                                            'label' => Yii::t('BbbModule.base', 'Mute on entry'),
                                        ])->hint(Yii::t('BbbModule.base', 'All users will be muted when entering the session.')); ?>
                                    </div>
                                    <div class="form-group">
                                        <?= $f->field($model, 'allowRecording')->checkbox([
                                            'label' => Yii::t('BbbModule.base', 'Allow recording this session.'),
                                        ])->hint(Yii::t('BbbModule.base', 'Recordings must be started manually.')); ?>
                                    </div>
                                    <div class="form-group">
                                        <?= $f->field($model, 'startParticipantsMinimized')->checkbox([
                                            'id' => 'bbb-rightbar-toggle',
                                            'label' => Yii::t('BbbModule.base', 'Start with left sidebar collapsed'),
                                        ])->hint(Yii::t('BbbModule.base', 'The panel on the left with participants will be slided out when participants join the session.')); ?>
                                    </div>
                                    <div class="form-group" id="bbb-chat-group" style="transition: opacity .2s ease;">
                                        <?= $f->field($model, 'startChatMinimized')->checkbox([
                                            'id' => 'bbb-chat-toggle',
                                            'label' => Yii::t('BbbModule.base', 'Start with chat minimized'),
                                        ])->hint(Yii::t('BbbModule.base', 'The chat panel will be collapsed when participants join the session. Not available when right sidebar is collapsed.')); ?>
                                    </div>
                                    <div class="form-group">
                                        <?= $f->field($model, 'layout')->radioList(
                                            Layouts::options(),
                                            [
                                                'item' => function ($index, $label, $name, $checked, $value) {
                                                    $desc = Layouts::descriptions()[$value] ?? '';
                                                    return "
                                                    <div class='form-check'>
                                                        <label>
                                                            <input type='radio' name='$name' value='$value' " . ($checked ? 'checked' : '') . ">
                                                            <strong>$label</strong><br>
                                                            <div class='hint-block'>$desc</div>
                                                        </label>
                                                    </div>";
                                                },
                                            ]
                                        ); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <?= $f->field($model, 'startPresentationHidden')->checkbox([
                                            'label' => Yii::t('BbbModule.base', 'Start with hidden presentation'),
                                        ])->hint(Yii::t('BbbModule.base', 'The presentation will be hidden when participants join the session.')); ?>
                                    </div>
                                    <?= FilePreviewField::widget([
                                        'form' => $f,
                                        'model' => $model,
                                        'attribute' => 'presentationUpload',
                                        'removeAttr' => 'removePresentation',
                                        'preview' => $model->presentationPreviewImage,
                                        'file' => $model->presentationFile,
                                        'label' => Yii::t('BbbModule.base', 'Upload presentation file.'),
                                        'changeLabel' => Yii::t('BbbModule.base', 'Change presentation file.'),
                                        'hint' => Yii::t('BbbModule.base', 'Optional presentation for this session. Use pdf in landscape mode.'),
                                    ]) ?>
                                    <?= FilePreviewField::widget([
                                        'form' => $f,
                                        'model' => $model,
                                        'attribute' => 'cameraBgImageUpload',
                                        'removeAttr' => 'removeCameraBgImage',
                                        'preview' => $model->cameraBgPreviewImage,
                                        'label' => Yii::t('BbbModule.base', 'Upload camera background image.'),
                                        'changeLabel' => Yii::t('BbbModule.base', 'Change camera background image.'),
                                        'hint' => Yii::t('BbbModule.base', 'Optional background image for user cameras. Recommended size: at least 800x600px.'),
                                    ]) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="card-footer">
                    <?= Html::submitButton(Yii::t('BbbModule.base', 'Save'), ['class' => 'btn btn-primary']); ?>
                    <?= Html::a(Yii::t('BbbModule.base', 'Cancel'), [$cancelUrl], ['class' => 'btn btn-secondary']); ?>
                </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>
    </div>
</div>

<?php
$msgPermission = json_encode(Yii::t('BbbModule.base', 'Not used if using permission system.'));
$msgWaiting = json_encode(Yii::t('BbbModule.base', 'Not compatible with the waiting room feature.'));
$msgJoin = json_encode(Yii::t('BbbModule.base', 'Not compatible with Join can start / Join can moderate.'));
$msgRightbar = json_encode(Yii::t('BbbModule.base', 'Not available when right sidebar is collapsed.'));

$this->registerJs("$(function(){
    var h = humhub.modules.BBBHelpers;
    h.setupDependent(['#join-by-permissions-toggle'], ['#user-picker-box'], $msgPermission, true);
    h.setupDependent(['#moderate-by-permissions-toggle'], ['#moderator-picker-box'], $msgPermission, true);
    h.setupDependent(['#bbb-rightbar-toggle'], ['#bbb-chat-group'], $msgRightbar);
    h.setupDependentViceVersa(['#bbb-waitingroom-toggle'], ['#bbb-joincanstart-toggle', '#bbb-joincanmoderate-toggle'], $msgWaiting, $msgJoin);
});");
