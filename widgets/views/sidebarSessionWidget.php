<?php
/**
 * Widget view: BBB sessions in the space right-column sidebar.
 *
 * @var array $rows                    Array of ['model' => Session, 'running' => bool]
 * @var \humhub\modules\content\components\ContentContainerActiveRecord|null $contentContainer
 */

use humhub\libs\Html;
use humhub\modules\ui\icon\widgets\Icon;
use k7zz\humhub\bbb\assets\BBBAssets;
use yii\helpers\Url;

$bundle = BBBAssets::register(view: $this);
$routeBase = '/bbb/session';
$routePrefix = $contentContainer
    ? $contentContainer->createUrl($routeBase)
    : $routeBase;
?>

<?php foreach ($rows as $row):
    /** @var k7zz\humhub\bbb\models\Session $model */
    $model = $row['model'];
    $running = $row['running'];
    $imageUrl = $model->outputImage
        ? $model->outputImage->getUrl()
        : $bundle->baseUrl . '/images/conference.png';
    $membersJoinLink = $routePrefix . '/join/' . $model->name;
    $title = $model->is_space_default
        ? Yii::t('BbbModule.base', 'Meet now')
        : $model->title;
    ?>
    <div class="panel panel-default bbb-sidebar-panel">
        <div class="panel-heading">
            <?= Icon::get('video-camera') ?><span style="margin-left: 10px;"><?= Html::encode($title) ?></span>
        </div>
        <img src="<?= Html::encode($imageUrl) ?>" alt="<?= Html::encode($model->title) ?>"
            style="width:100%; display:block;">
        <div class="panel-body" style="padding-bottom: 8px;">

            <?= Html::encode($model->description) ?>

            <div class="d-grid gap-1 mt-2">
                <?php if ($running && $model->canJoin()): ?>
                    <?= Html::a(
                        Icon::get('video-camera') . ' ' . Yii::t('BbbModule.base', 'Join'),
                        '#',
                        [
                            'class' => 'btn btn-primary btn-sm w-70 bbb-launch-window',
                            'data-url' => $membersJoinLink,
                            'title' => Yii::t('BbbModule.base', 'Join session'),
                        ]
                    ) ?>
                <?php elseif (!$running && $model->canStart()): ?>
                    <?= Html::a(
                        Icon::get('video-camera') . ' ' . Yii::t('BbbModule.base', 'Start'),
                        '#',
                        [
                            'class' => 'btn btn-primary btn-sm w-70 bbb-launch-window',
                            'data-url' => $routePrefix . '/start/' . $model->name . '?embed=0',
                            'title' => Yii::t('BbbModule.base', 'Start session'),
                        ]
                    ) ?>
                <?php endif; ?>

                <span id="bbb-sidebar-members-url-<?= $model->id ?>"
                    class="d-none"><?= Url::to([$membersJoinLink], true) ?></span>
                <?php if (!$model->is_space_default): ?>
                    <?= Html::a(
                        Icon::get('clipboard') . ' ' . Yii::t('BbbModule.base', 'Copy Members join link'),
                        '#',
                        [
                            'class' => 'btn btn-outline-danger btn-sm',
                            'title' => Yii::t('BbbModule.base', 'Copy members access URL to clipboard'),
                            'data-action-click' => 'copyToClipboard',
                            'data-action-target' => '#bbb-sidebar-members-url-' . $model->id,
                        ]
                    ) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
<?php endforeach; ?>