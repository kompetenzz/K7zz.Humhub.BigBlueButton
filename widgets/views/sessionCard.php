<?php
/**
 * @var k7zz\humhub\bbb\models\Session $model
 * @var bool   $running
 * @var string $scope  'container' | 'global'
 * @var int    $highlightId
 */
use humhub\modules\ui\icon\widgets\Icon;
use yii\helpers\Html;

$routePrefix = '/bbb/session';
$highlightClass = $model->id === $highlightId ? 'highlight' : '';
?>
<div class="col-sm-6 col-md-4 col-lg-3">
    <div class="card card-default <?= $highlightClass ?>" style="min-height:170px">
        <div class="card-heading">
            <strong><?= Html::encode($model->title) ?></strong>
        </div>

        <div class="card-body text-center">
            <?php if ($model->outputImage !== null) { ?>
                <img src="<?= $model->outputImage->getUrl() ?>" class="img-responsive img-thumbnail"
                    alt="<?= Yii::t('BbbModule.base', 'Session image') ?>"
                    style="max-height: 400px; max-width: 100%; margin-bottom: 10px;" class="card-img-top">
            <?php }
            if (!empty($model->description)): ?>
                <p class="text-muted" style="min-height:48px">
                    <?= Html::encode($model->description) ?>
                </p>
            <?php else: ?>
                <p style="min-height:48px"></p>
            <?php endif; ?>


            <p>
                <?= $running
                    ? '<span class="text-success">' . Icon::get('play') . ' ' . Yii::t('BbbModule.base', 'Running') . '</span>'
                    : '<span class="text-warning">' . Icon::get('pause') . ' ' . Yii::t('BbbModule.base', 'Stopped') . '</span>' ?>
            </p>

            <div class="card-footer text-center">
                <?php if ($running && $model->canJoin()): ?>
                    <?= Html::a(
                        Icon::get('window-maximize') . ' ' . Yii::t('BbbModule.base', 'Join'),
                        [$routePrefix . '/embed', 'slug' => $model->name],
                        ['class' => 'btn btn-success btn-sm', 'title' => Yii::t('BbbModule.base', 'Start in embedded mode') . ' – ' . Yii::t('BbbModule.base', 'recommended')]
                    ) ?>
                    <?= Html::a(
                        Icon::get('window-restore') . ' ' . Yii::t('BbbModule.base', 'Join'),
                        [$routePrefix . '/join', 'slug' => $model->name],
                        [
                            'target' => '_bbb',
                            'class' => 'btn btn-primary btn-sm',
                            'title' => Yii::t('BbbModule.base', 'Start in new tab') . ' – ' . Yii::t('BbbModule.base', 'not recommended')
                        ],
                    ) ?>
                <?php elseif (!$running && $model->canStart()): ?>
                    <?= Html::a(
                        Icon::get('window-maximize') . ' ' . Yii::t('BbbModule.base', 'Start'),
                        [$routePrefix . '/start', 'slug' => $model->name, 'embed' => 1],
                        ['class' => 'btn btn-success btn-sm', 'title' => Yii::t('BbbModule.base', 'Start in embedded mode') . ' – ' . Yii::t('BbbModule.base', 'recommended')]
                    ) ?>
                    <?= Html::a(
                        Icon::get('window-restore') . ' ' . Yii::t('BbbModule.base', 'Start'),
                        [$routePrefix . '/start', 'slug' => $model->name, 'embed' => 0],
                        [
                            'target' => '_bbb',
                            'class' => 'btn btn-primary btn-sm',
                            'title' => Yii::t('BbbModule.base', 'Start in new tab') . ' – ' . Yii::t('BbbModule.base', 'not recommended')
                        ],
                    ) ?>
                <?php endif; ?>
                <?php if ($model->canAdminister()): ?>
                    <?= Html::a(
                        Icon::get('pencil') . ' ' . Yii::t('BbbModule.base', 'Edit'),
                        [$routePrefix . '/edit/' . $model->name],
                        ['class' => 'btn btn-danger btn-sm']
                    ) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>