<?php
use humhub\libs\Html;
use humhub\modules\ui\icon\widgets\Icon;
use k7zz\humhub\bbb\assets\BBBAssets;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $session \k7zz\humhub\bbb\models\Session */
/* @var $running bool */
/* @var $canStart bool */
/* @var $startUrl string */
/* @var $joinUrl string */
/* @var $isRunningUrl string */
/* @var $preMeetingChats \k7zz\humhub\bbb\models\SessionMeetingChat[] */

$bundle = BBBAssets::register($this);
$this->setPageTitle($session->title);
$imageUrl = $session->outputImage
    ? $session->outputImage->getUrl()
    : $bundle->baseUrl . '/images/conference.png';

$routePrefix = '/bbb/session';
if ($this->context->contentContainer) {
    $routePrefix = $this->context->contentContainer->createUrl($routePrefix);
}
$sessionLink = $routePrefix . '/' . $session->name;
?>
<div id="layout-content" data-bbb-check-state="<?= Html::encode($isRunningUrl) ?>">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 <?= $chatEnabled ? 'col-xl-10 offset-xl-1' : 'col-xl-7 mx-auto' ?>">
                <div class="card bbb-session-card">

                    <?php if ($chatEnabled): ?>
                    <div class="row g-0 align-items-stretch">

                        <!-- LEFT: Session info + action buttons -->
                        <div class="col-md-7 bbb-session-left d-flex flex-column">
                    <?php else: ?>
                        <div class="bbb-session-left d-flex flex-column">
                    <?php endif; ?>

                            <?= $this->renderFile('@bbb/views/session/_sessionDetails.php', [
                                'session'  => $session,
                                'running'  => $running,
                                'imageUrl' => $imageUrl,
                                'top'      => true,
                            ]) ?>

                            <div class="card-body mt-auto">
                                <div class="alert alert-danger bbb-hook-warning" style="display:none;">
                                    <?= Icon::get('exclamation-triangle') ?>
                                    <?= Yii::t('BbbModule.base', 'No BBB webhook events are being received. The bbb-webhooks service may not be running or is misconfigured. Chat integration and meeting status tracking will not work.') ?>
                                </div>
                                <div class="bbb-waiting" style="display: <?= $running ? 'none' : '' ?>;">
                                    <?php if ($canStart): ?>
                                        <?= Html::a(
                                            Icon::get('video-camera') . ' ' . Yii::t('BbbModule.base', 'Start'),
                                            '#',
                                            [
                                                'class'    => 'btn btn-primary btn-lg w-100 bbb-launch-window',
                                                'data-url' => $startUrl,
                                                'title'    => Yii::t('BbbModule.base', 'Start session'),
                                            ]
                                        ) ?>
                                    <?php else: ?>
                                        <?= Html::a(
                                            Icon::get('clock') . ' ' . Yii::t('BbbModule.base', 'Enter waitingroom'),
                                            '#',
                                            [
                                                'class'    => 'btn btn-primary btn-lg w-100 bbb-launch-window',
                                                'data-url' => $joinUrl,
                                                'title'    => Yii::t('BbbModule.base', 'Enter the waitingroom until the session starts'),
                                            ]
                                        ) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="bbb-running" style="display: <?= $running ? '' : 'none' ?>;">
                                    <a href="#" class="btn btn-success btn-lg w-100 bbb-launch-window"
                                        data-url="<?= Html::encode($joinUrl) ?>">
                                        <?= Icon::get('sign-in') . ' ' . Yii::t('BbbModule.base', 'Join now') ?>
                                    </a>
                                </div>
                            </div>

                            <div class="card-footer bbb-session-footer">
                                <span id="bbb-members-url-<?= $session->id ?>" class="d-none"><?= Url::to([$sessionLink], true) ?></span>
                                <?= Html::a(
                                    Icon::get('lock') . ' ' . Yii::t('BbbModule.base', 'Members join link'),
                                    '#',
                                    [
                                        'class'               => 'btn btn-danger btn-sm',
                                        'title'               => Yii::t('BbbModule.base', 'Copy members access URL to clipboard'),
                                        'data-action-click'   => 'copyToClipboard',
                                        'data-action-target'  => '#bbb-members-url-' . $session->id,
                                    ]
                                ) ?>
                                <?php if ($session->canAdminister() && $session->public_join && $session->public_token): ?>
                                    <span id="bbb-public-url-<?= $session->id ?>" class="d-none"><?= Url::to('/bbb/public/join/' . $session->public_token, true) ?></span>
                                    <?= Html::a(
                                        Icon::get('link') . ' ' . Yii::t('BbbModule.base', 'Public Join link'),
                                        '#',
                                        [
                                            'class'              => 'btn btn-success btn-sm',
                                            'title'              => Yii::t('BbbModule.base', 'Copy public access URL to clipboard'),
                                            'data-action-click'  => 'copyToClipboard',
                                            'data-action-target' => '#bbb-public-url-' . $session->id,
                                        ]
                                    ) ?>
                                <?php endif; ?>
                                <?php if ($session->canAdminister()): ?>
                                    <span class="float-end">
                                        <?= Html::a(
                                            Icon::get('pencil'),
                                            $routePrefix . '/edit/' . $session->name,
                                            [
                                                'class' => 'btn btn-info btn-sm',
                                                'title' => Yii::t('BbbModule.base', 'Edit session'),
                                            ]
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if (!$chatEnabled && $recordingsEnabled): ?>
                            <?= $this->renderFile('@bbb/views/session/_recordingsBox.php', [
                                'model'            => $session,
                                'contentContainer' => $this->context->contentContainer,
                                'chatEnabled'      => false,
                            ]) ?>
                            <?php endif; ?>

                        </div><!-- /.bbb-session-left -->

                    <?php if ($chatEnabled): ?>
                        <!-- RIGHT: Chat + Recordings -->
                        <div class="col-md-5 bbb-session-right d-flex flex-column">
                            <?= $this->renderFile('@bbb/views/session/_chatBox.php', [
                                'session'  => $session,
                                'running'  => $running,
                                'messages' => $preMeetingChats ?? [],
                            ]) ?>
                            <?= $this->renderFile('@bbb/views/session/_recordingsBox.php', [
                                'model'            => $session,
                                'contentContainer' => $this->context->contentContainer,
                                'chatEnabled'      => true,
                            ]) ?>
                        </div>

                    </div><!-- /.row -->
                    <?php endif; ?>

                </div><!-- /.card -->
            </div>
        </div>
    </div>
</div>
