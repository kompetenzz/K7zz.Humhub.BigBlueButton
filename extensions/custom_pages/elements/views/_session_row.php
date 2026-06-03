<?php
/**
 * Single BBB session — compact list row style.
 *
 * @var \k7zz\humhub\bbb\models\Session $session
 * @var bool $running
 * @var \k7zz\humhub\bbb\assets\BBBAssets $bundle
 * @var bool $isFirst
 * @var bool $isLast
 */

use humhub\libs\Html;
use humhub\modules\topic\models\Topic;
use humhub\modules\topic\widgets\TopicBadge;
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
<div id="bbb-sidebar-session-<?= $session->id ?>"
     class="bbb-list-row<?= $isLast ? ' bbb-list-row--last' : '' ?>"
     data-bbb-check-state="<?= Html::encode($isRunningUrl) ?>">

    <a href="<?= Html::encode($sessionLink) ?>" class="bbb-list-thumb">
        <img src="<?= Html::encode($imageUrl) ?>" alt="<?= Html::encode($title) ?>">
    </a>

    <div class="bbb-list-title">
        <a href="<?= Html::encode($sessionLink) ?>"><strong><?= Html::encode($title) ?></strong></a>
        <span class="badge bg-success bbb-live-badge bbb-running" style="display:<?= $running ? '' : 'none' ?>;">
            <span class="bbb-live-dot"></span> <?= Yii::t('BbbModule.base', 'Live') ?>
        </span>
    </div>

    <div class="bbb-list-desc">
        <?php $topics = Topic::findByContent($session->content)->all(); ?>
        <?php if (!empty($topics)): ?>
            <div class="topic-label-list d-flex gap-1 flex-wrap mb-1">
                <?php foreach ($topics as $topic): ?>
                    <?= TopicBadge::forTopic($topic, $session->content) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?= RichText::output($session->description) ?>
    </div>

    <div class="bbb-list-actions">
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
                    ['class' => 'btn btn-primary btn-sm bbb-launch-window',
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
                Icon::get('clipboard'),
                '#',
                ['class' => 'btn btn-outline-secondary btn-sm',
                 'title' => Yii::t('BbbModule.base', 'Copy members access URL to clipboard'),
                 'data-action-click' => 'copyToClipboard',
                 'data-action-target' => '#bbb-sidebar-members-url-' . $session->id]
            ) ?>
        <?php endif; ?>
    </div>
</div>
