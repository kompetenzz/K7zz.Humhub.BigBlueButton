<?php
/**
 * Widget view: Single session as a card element.
 *
 * @var k7zz\humhub\bbb\models\Session $model           The session model
 * @var bool   $running                                   Whether the session is currently running
 * @var int    $highlightId                               ID of the session to highlight
 * @var \humhub\modules\content\components\ContentContainerActiveRecord|null $contentContainer  The content container (space/user) or null
 */
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\custom_pages\modules\template\models\RichtextContent;
use humhub\modules\ui\icon\widgets\Icon;
use k7zz\humhub\bbb\assets\BBBAssets;
use k7zz\humhub\bbb\widgets\RecordingsList;
use yii\helpers\Url;
use humhub\libs\Html;

$bundle = BBBAssets::register(view: $this);
$routePrefix = '/bbb/session';
if ($this->context->contentContainer) {
    $routePrefix = $this->context->contentContainer->createUrl($routePrefix);
}
$highlightClass = $model->id === $highlightId ? 'highlight' : '';
$imageUrl = $model->outputImage ? $model->outputImage->getUrl() : $bundle->baseUrl . '/images/conference.png';
$membersJoinLink = $routePrefix . '/join/' . $model->name;

?>

<div id="sessioncard-<?= $model->id ?>"
    class="col-lg-3 col-md-4 col-sm-6 col-12 card-bbb-sessions <?= $highlightClass ?>">
    <div class="card">
        <img class="card-img-top" alt="<?= Yii::t('BbbModule.base', 'Session image') ?>" style="max-height: 200px; object-fit: cover;"
            src="<?= Html::encode($imageUrl) ?>" />
        <div class="card-body">
            <h5 class="card-title"><?= $model->title ?>
                <span class="float-end">
                    <?= $running
                        ? '<span class="text-success" title="' . Yii::t('BbbModule.base', 'Running') . '">' . Icon::get('play') . '</span>'
                        : '<span class="text-warning" title="' . Yii::t('BbbModule.base', 'Stopped') . '">' . Icon::get('pause') . '</span>' ?>
                    <?php if ($model->isModerator()): ?>
                        <span class="text-info"
                            title="<?= Yii::t('BbbModule.base', 'You are moderator') ?>"><?= Icon::get('user-secret') ?></span>
                    <?php endif; ?>
                </span>
            </h5>
            <p class="card-text">
                <?= RichText::output($model->description) ?>
            </p>
        </div>
        <?php if ($model->canJoin()): ?>
            <div class="card-footer">
                <?php if ($running && $model->canJoin()): ?>
                    <?= Html::a(
                        Icon::get('video-camera') . ' ' . Yii::t('BbbModule.base', 'Join'),
                        '#',
                        [
                            'class' => 'btn btn-primary btn-sm bbb-launch-window',
                            'data-url' => $membersJoinLink,
                            'title' => Yii::t('BbbModule.base', 'Join session'),
                        ]
                    ) ?>
                <?php elseif (!$running && $model->canStart()): ?>
                    <?= Html::a(
                        Icon::get('video-camera') . ' ' . Yii::t('BbbModule.base', 'Start'),
                        '#',
                        [
                            'class' => 'btn btn-primary btn-sm bbb-launch-window',
                            'data-url' => $routePrefix . '/start/' . $model->name . '?embed=0',
                            'title' => Yii::t('BbbModule.base', 'Start session'),
                        ]
                    ) ?>
                <?php endif; ?>

                <span id="bbb-members-url-<?= $model->id ?>" class="d-none"><?= Url::to([$membersJoinLink], true) ?></span>
                <?= Html::a(
                    Icon::get('lock') . ' ' . Yii::t('BbbModule.base', 'Members join link'),
                    '#',
                    [
                        'class' => 'btn btn-danger btn-sm',
                        'title' => Yii::t('BbbModule.base', 'Copy members access URL to clipboard'),
                        'data-action-click' => 'copyToClipboard',
                        'data-action-target' => '#bbb-members-url-' . $model->id
                    ]
                ) ?>

                <?php if ($model->public_join && $model->public_token): ?>
                    <span id="bbb-public-url-<?= $model->id ?>"
                        class="d-none"><?= Url::to(['/bbb/public/join', 'token' => $model->public_token], true) ?></span>
                    <?= Html::a(
                        Icon::get('link') . ' ' . Yii::t('BbbModule.base', 'Public Join link'),
                        '#',
                        [
                            'class' => 'btn btn-success btn-sm',
                            'title' => Yii::t('BbbModule.base', 'Copy public access URL to clipboard'),
                            'data-action-click' => 'copyToClipboard',
                            'data-action-target' => '#bbb-public-url-' . $model->id
                        ]
                    ) ?>
                <?php endif; ?>

                <?php if ($model->canAdminister()): ?>
                    <span class="float-end">
                        <?= Html::a(
                            Icon::get('pencil'),
                            $routePrefix . '/edit/' . $model->name,
                            [
                                'class' => 'btn btn-info btn-sm',
                                'title' => Yii::t('BbbModule.base', 'Edit session')
                            ]
                        ) ?>
                        <?= Html::a(
                            Icon::get('trash'),
                            $routePrefix . '/delete/' . $model->name,
                            [
                                'class' => 'btn btn-danger btn-sm',
                                'data-confirm' => Yii::t('BbbModule.base', 'Are you sure you want to delete this session?'),
                                'data-method' => 'post',
                                'title' => Yii::t('BbbModule.base', 'Delete session'),
                                'aria-label' => Yii::t('BbbModule.base', 'Delete session'),
                            ]
                        ) ?>
                    </span>
                <?php endif; ?>
            </div>
            <?php if ($model->canAdminister()): ?>
                <div id="sessioncard-recordingsbox-<?= $model->id ?>" class="card-footer">
                    <?= RecordingsList::widget(['sessionId' => $model->id, 'contentContainer' => $this->context->contentContainer, 'canAdminister' => $model->canAdminister()]) ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$getRecordingsCountUrlBase = '/bbb/session/recordings-count';
$getRecordingsCountUrl = $this->context->contentContainer
    ? $this->context->contentContainer->createUrl($getRecordingsCountUrlBase, ['id' => $model->id])
    : Url::to($getRecordingsCountUrlBase . "?id=" . $model->id);
$this->registerJs(<<<JS

$.getJSON('{$getRecordingsCountUrl}', function(recordingsCount) {
    $('#sessioncard-recordingsbox-{$model->id}').toggle(recordingsCount > 0);
});
JS
    ,
    \yii\web\View::POS_READY
);
?>