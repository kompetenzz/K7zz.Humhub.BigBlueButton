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
use yii\helpers\Url;

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
                <span class="pull-right">
                    <?= $running
                        ? '<span class="text-success" title="' . Yii::t('BbbModule.base', 'Running') . '">' . Icon::get('play') . '</span>'
                        : '<span class="text-warning" title="' . Yii::t('BbbModule.base', 'Stopped') . '">' . Icon::get('pause') . '</span>' ?>
                    <?php if ($model->isModerator()): ?>
                        <span class="text-info"
                            title="<?= Yii::t('BbbModule.base', 'You are moderator') ?>"><?= Icon::get('user-secret') ?></span>
                    </span>
                <?php endif; ?>
            </h5>
            <p class="card-text">
                <?= Html::encode($model->description) ?>
            </p>
        </div>
        <?php if ($model->canJoin()): ?>

            <!-- Fußzeile mit Action-Links-->
            <div class="panel-footer" style="padding-top: 10px">
                <?php if ($running && $model->canJoin()): ?>
                    <?= Html::a(
                        Icon::get('video-camera') . ' ' . Yii::t('BbbModule.base', 'Join'),
                        '#',
                        [
                            'class' => 'btn btn-primary btn-sm bbb-launch-window',
                            'data-url' => $routePrefix . '/join/' . $model->name,
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
                    ) ?>     <?php endif; ?>

                <?php if ($model->canAdminister()): ?>
                    <span class="pull-right">
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
                <div id="sessioncard-recordingsbox-<?= $model->id ?>" class="panel-footer" style="padding-top: 10px">
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