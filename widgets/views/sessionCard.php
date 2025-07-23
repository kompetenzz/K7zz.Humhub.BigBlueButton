<?php
/**
 * @var k7zz\humhub\bbb\models\Session $model
 * @var bool   $running
 * @var string $scope  'container' | 'global'
 * @var int    $highlightId
 */
use humhub\modules\ui\icon\widgets\Icon;
use k7zz\humhub\bbb\assets\BBBAssets;
use k7zz\humhub\bbb\widgets\RecordingsList;
use yii\helpers\Html;
$bundle = BBBAssets::register(view: $this);
$routePrefix = '/bbb/session';
if ($this->context->contentContainer) {
    $routePrefix = $this->context->contentContainer->createUrl($routePrefix);
}
$highlightClass = $model->id === $highlightId ? 'highlight' : '';
$imageUrl = $model->outputImage ? $model->outputImage->getUrl() : $bundle->baseUrl . '/images/conference.png';
?>



<div id="sessioncard-<?= $model->id ?>"
    class="card card card-space col-lg-3 col-md-4 col-sm-6 col-xs-12 card-bbb-sessions <?= $highlightClass ?>">
    <div class="card-panel">
        <div class="card-heading panel-heading">
            <img class="" alt="<?= Yii::t('BbbModule.base', 'Session image') ?>" style="max-height: 200px; width: 100%"
                src="<?= Html::encode($imageUrl) ?>" />
        </div>
        <div class="card-body panel-body">
            <h5 class="card-title"><?= $model->title ?>
                <?= $running
                    ? '<big class="text-success pull-right" title="' . Yii::t('BbbModule.base', 'Running') . '">' . Icon::get('play') . '</big>'
                    : '<big class="text-warning pull-right" title="' . Yii::t('BbbModule.base', 'Stopped') . '">' . Icon::get('pause') . '</big>' ?>
                <?php if ($model->isModerator()): ?>
                    <big class="text-info pull-right"
                        title="<?= Yii::t('BbbModule.base', 'You are moderator') ?>"><?= Icon::get('user-secret') ?></big>
                <?php endif; ?>
            </h5>
            <p class="card-text">
                <?= Html::encode($model->description) ?>
            </p>
        </div>
        <!-- Fußzeile mit Action-Links-->
        <div class="panel-footer" style="padding-top: 10px">
            <?php if ($running && $model->canJoin()): ?>
                <?= Html::a(
                    Icon::get('window-maximize') . ' ' . Yii::t('BbbModule.base', 'Join'),
                    $routePrefix . '/embed/' . $model->name,
                    ['class' => 'btn btn-success btn-sm', 'title' => Yii::t('BbbModule.base', 'Start in embedded mode') . ' – ' . Yii::t('BbbModule.base', 'recommended')]
                ) ?>
                <?= Html::a(
                    Icon::get('window-restore') . ' ' . Yii::t('BbbModule.base', 'Join'),
                    $routePrefix . '/join/' . $model->name,
                    [
                        'target' => '_bbb',
                        'class' => 'btn btn-primary btn-sm',
                        'title' => Yii::t('BbbModule.base', 'Start in new tab') . ' – ' . Yii::t('BbbModule.base', 'not recommended')
                    ],
                ) ?>
            <?php elseif (!$running && $model->canStart()): ?>
                <?= Html::a(
                    Icon::get('window-maximize') . ' ' . Yii::t('BbbModule.base', 'Start'),
                    [$routePrefix . '/start/' . $model->name, 'embed' => 1],
                    ['class' => 'btn btn-success btn-sm', 'title' => Yii::t('BbbModule.base', 'Start in embedded mode') . ' – ' . Yii::t('BbbModule.base', 'recommended')]
                ) ?>
                <?= Html::a(
                    Icon::get('window-restore') . ' ' . Yii::t('BbbModule.base', 'Start'),
                    [$routePrefix . '/start/' . $model->name, 'embed' => 0],
                    [
                        'target' => '_bbb',
                        'class' => 'btn btn-primary btn-sm',
                        'title' => Yii::t('BbbModule.base', 'Start in new tab') . ' – ' . Yii::t('BbbModule.base', 'not recommended')
                    ],
                ) ?>
            <?php endif; ?>

            <?php if ($model->canAdminister()): ?>
                <?= Html::a(
                    Icon::get('pencil'),
                    $routePrefix . '/edit/' . $model->name,
                    [
                        'class' => 'btn btn-info btn-sm pull-right',
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
            <?php endif; ?>
        </div>
        <div id="sessioncard-recordingsbox-<?= $model->id ?>" class="panel-footer" style="padding-top: 10px">
            <?= RecordingsList::widget(['sessionId' => $model->id, 'contentContainer' => $this->context->contentContainer]) ?>
        </div>
    </div>
</div>

<?php
$getRecordingsCountUrlBase = '/bbb/session/recordings-count';
$getRecordingsCountUrl = $this->context->contentContainer
    ? $this->context->contentContainer->createUrl($getRecordingsCountUrlBase, ['id' => $model->id])
    : Url::to($getRecordingsCountUrlBase, ['id' => $model->id]);
$this->registerJs(<<<JS

$.getJSON('{$getRecordingsCountUrl}', function(recordingsCount) {
    $('#sessioncard-recordingsbox-{$model->id}').toggle(recordingsCount > 0);
});
JS
    ,
    \yii\web\View::POS_READY
);
?>