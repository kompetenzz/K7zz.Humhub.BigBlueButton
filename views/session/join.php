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

$bundle = BBBAssets::register($this);
$this->setPageTitle($session->title);
$imageUrl = $session->outputImage
    ? $session->outputImage->getUrl()
    : $bundle->baseUrl . '/images/conference.png';
?>
<div id="layout-content" data-bbb-check-running="<?= Html::encode($isRunningUrl) ?>" data-bbb-redirect-on-running>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
                <div class="card">
                    <?= $this->renderFile('@bbb/views/session/_sessionDetails.php', [
                        'session' => $session,
                        'running' => $running,
                        'imageUrl' => $imageUrl,
                        'top' => true,
                    ]) ?>

                    <div class="card-body">
                        <div class="bbb-waiting" class="alert alert-info"
                            style="display: <?= $running ? 'none' : '' ?>;">
                            <i class="fa fa-spinner fa-spin"></i>
                            <?= Yii::t('BbbModule.base', 'The session has not started yet.') ?>
                        </div>

                        <div class="bbb-running" style="display: <?= $running ? '' : 'none' ?>;">
                            <div class="alert alert-success">
                                <i class="fa fa-check"></i>
                                <?= Yii::t('BbbModule.base', 'The session has started!') ?>
                            </div>
                            <a href="#" class="btn btn-success btn-lg w-100 bbb-launch-window"
                                data-url="<?= Html::encode($joinUrl) ?>">
                                <?= Icon::get('sign-in') . ' ' . Yii::t('BbbModule.base', 'Join now') ?>
                            </a>
                        </div>

                        <?php if ($canStart): ?>
                            <?= Html::a(
                                Icon::get('play') . ' ' . Yii::t('BbbModule.base', 'Start session'),
                                $startUrl,
                                ['class' => 'btn btn-primary btn-lg w-100 mt-2']
                            ) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>