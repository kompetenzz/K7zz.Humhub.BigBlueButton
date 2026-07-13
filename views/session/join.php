<?php
use humhub\libs\Html;
use humhub\modules\ui\icon\widgets\Icon;
use k7zz\humhub\bbb\assets\BBBAssets;

/* @var $this \yii\web\View */
/* @var $session \k7zz\humhub\bbb\models\Session */
/* @var $canStart bool */
/* @var $startUrl string */
/* @var $isRunningUrl string */
/* @var $joinUrl string */
/* @var $running bool */
/* @var $preMeetingChats \k7zz\humhub\bbb\models\SessionMeetingChat[] */

$bundle = BBBAssets::register($this);
$this->setPageTitle($session->title);
$imageUrl = $session->outputImage
    ? $session->outputImage->getUrl()
    : $bundle->baseUrl . '/images/conference.png';
?>
<div id="layout-content" data-bbb-check-state="<?= Html::encode($isRunningUrl) ?>" data-bbb-redirect-on-change
    data-bbb-redirect-state="<?= $running ? 'running' : 'waiting' ?>">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 col-xl-10 offset-xl-1">
                <div class="card bbb-session-card">
                    <div class="row g-0 align-items-stretch">

                        <!-- LEFT: Session info + waiting/join state -->
                        <div class="col-md-7 bbb-session-left d-flex flex-column">
                            <?= $this->renderFile('@bbb/views/session/_sessionDetails.php', [
                                'session'  => $session,
                                'running'  => $running,
                                'imageUrl' => $imageUrl,
                                'top'      => true,
                            ]) ?>

                            <div class="card-body mt-auto">
                                <div class="bbb-waiting" style="display: <?= $running ? 'none' : '' ?>;">
                                    <div class="alert alert-info mb-3">
                                        <?= Icon::get('spinner fa-spin') ?>
                                        <?= Yii::t('BbbModule.base', 'The session has not started yet.') ?>
                                    </div>
                                    <?php if ($canStart): ?>
                                        <?= Html::a(
                                            Icon::get('play') . ' ' . Yii::t('BbbModule.base', 'Start session'),
                                            $startUrl,
                                            ['class' => 'btn btn-primary btn-lg w-100']
                                        ) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="bbb-running" style="display: <?= $running ? '' : 'none' ?>;">
                                    <div class="alert alert-success mb-3">
                                        <?= Icon::get('check') ?>
                                        <?= Yii::t('BbbModule.base', 'The session has started!') ?>
                                    </div>
                                    <a href="#" class="btn btn-success btn-lg w-100 bbb-launch-window"
                                        data-url="<?= Html::encode($joinUrl) ?>">
                                        <?= Icon::get('sign-in') . ' ' . Yii::t('BbbModule.base', 'Join now') ?>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT: Chat -->
                        <div class="col-md-5 bbb-session-right d-flex flex-column">
                            <?= $this->renderFile('@bbb/views/session/_chatBox.php', [
                                'session'  => $session,
                                'running'  => $running,
                                'messages' => $preMeetingChats ?? [],
                            ]) ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
