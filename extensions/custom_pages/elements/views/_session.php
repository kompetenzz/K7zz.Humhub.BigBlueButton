<?php
/**
 * Single BBB session — sidebar panel style.
 *
 * @var \k7zz\humhub\bbb\models\Session $session
 * @var bool $running
 * @var \k7zz\humhub\bbb\assets\BBBAssets $bundle
 */

use humhub\libs\Html;
use humhub\modules\ui\icon\widgets\Icon;
use humhub\modules\content\widgets\richtext\RichText;
use yii\helpers\Url;

$container = $session->content->container;
$routeBase = '/bbb/session';
$routePrefix = $container ? $container->createUrl($routeBase) : $routeBase;

$imageUrl = $session->outputImage
    ? $session->outputImage->getUrl()
    : $bundle->baseUrl . '/images/conference.png';

$membersJoinLink = $routePrefix . '/join/' . $session->name;
$sessionLink     = $routePrefix . '/' . $session->name;
$isRunningUrl    = $routePrefix . '/is-running?id=' . $session->id;

$title = $session->is_space_default
    ? ($container instanceof \humhub\modules\user\models\User
        ? Yii::t('BbbModule.base', 'Meet me')
        : Yii::t('BbbModule.base', 'Meet now'))
    : $session->title;
?>
<div id="bbb-sidebar-session-<?= $session->id ?>" class="panel panel-default bbb-sidebar-panel"
     data-bbb-check-state="<?= Html::encode($isRunningUrl) ?>">

    <div class="panel-heading" style="display:flex; align-items:center; justify-content:space-between;">
        <a href="<?= Html::encode($sessionLink) ?>">
            <span><?= Icon::get('video-camera') ?> <?= Html::encode($title) ?></span>
            <span class="badge bg-success bbb-live-badge bbb-running" style="display:<?= $running ? '' : 'none' ?>;">
                <span class="bbb-live-dot"></span>
                <?= Yii::t('BbbModule.base', 'Live') ?>
            </span>
        </a>
    </div>

    <a href="<?= Html::encode($sessionLink) ?>">
        <img src="<?= Html::encode($imageUrl) ?>" alt="<?= Html::encode($session->title) ?>"
             style="width:100%; height:auto; object-fit:cover; display:block;">
    </a>

    <div class="panel-body" style="padding-bottom:8px;">
        <?= RichText::output($session->description) ?>

        <div class="d-grid gap-1 mt-2">
            <div class="bbb-waiting" style="display:<?= $running ? 'none' : '' ?>;">
                <?php if ($session->canStart()): ?>
                    <?= Html::a(
                        Icon::get('video-camera') . ' ' . Yii::t('BbbModule.base', 'Start'),
                        '#',
                        ['class' => 'btn btn-primary btn-sm bbb-launch-window',
                         'data-url' => $routePrefix . '/start/' . $session->name . '?embed=0',
                         'title' => Yii::t('BbbModule.base', 'Start session')]
                    ) ?>
                <?php elseif ($session->canJoin()): ?>
                    <?= Html::a(
                        Icon::get('clock') . ' ' . Yii::t('BbbModule.base', 'Enter waitingroom'),
                        '#',
                        ['class' => 'btn btn-primary btn-sm bbb-launch-window',
                         'data-url' => $membersJoinLink,
                         'title' => Yii::t('BbbModule.base', 'Enter the waitingroom until the session starts')]
                    ) ?>
                <?php endif; ?>
            </div>
            <div class="bbb-running" style="display:<?= $running ? '' : 'none' ?>;">
                <?php if ($session->canJoin()): ?>
                    <?= Html::a(
                        Icon::get('video-camera') . ' ' . Yii::t('BbbModule.base', 'Join'),
                        '#',
                        ['class' => 'btn btn-primary btn-sm w-100 bbb-launch-window',
                         'data-url' => $membersJoinLink,
                         'title' => Yii::t('BbbModule.base', 'Join session')]
                    ) ?>
                <?php endif; ?>
            </div>
            <?php if (!$session->is_space_default): ?>
                <span id="bbb-sidebar-members-url-<?= $session->id ?>" class="d-none">
                    <?= Url::to([$sessionLink], true) ?>
                </span>
                <?= Html::a(
                    Icon::get('clipboard') . ' ' . Yii::t('BbbModule.base', 'Copy Members join link'),
                    '#',
                    ['class' => 'btn btn-outline-danger btn-sm',
                     'title' => Yii::t('BbbModule.base', 'Copy members access URL to clipboard'),
                     'data-action-click' => 'copyToClipboard',
                     'data-action-target' => '#bbb-sidebar-members-url-' . $session->id]
                ) ?>
            <?php endif; ?>
        </div>
    </div>
</div>
