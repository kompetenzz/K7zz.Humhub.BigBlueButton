<?php
use humhub\helpers\Html;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\ui\icon\widgets\Icon;
use k7zz\humhub\bbb\assets\BBBAssets;
use yii\helpers\Url;
use k7zz\humhub\bbb\widgets\LiveStats;

/* @var $this \yii\web\View */
/* @var $session \k7zz\humhub\bbb\models\Session|null */
/* @var $token string */
/* @var $msg string|null */
/* @var $running bool */

BBBAssets::register($this);
$this->setPageTitle(($session && $session->title) ? $session->title : Yii::t('BbbModule.base', 'Join session'));
$action = Url::to('/bbb/public/join/' . $token, true);
?>
<div id="layout-content"
    data-bbb-check-state="<?= Html::encode(Url::to(['/bbb/public/is-running', 'token' => $token])) ?>"
    data-bbb-redirect-on-change data-bbb-state="<?= $running ? 'running' : 'waiting' ?>">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
                <div class="card">
                    <?php if ($session): ?>
                        <div class="card-header" style="padding: 0; overflow: hidden;">
                            <img src="<?= Html::encode($session->getPublicImageUrl()) ?>" alt="<?= Html::encode($session->title) ?>"
                                style="width: 100%; height: auto; object-fit: cover; display: block;">
                        </div>
                    <?php endif; ?>

                    <div class="card-body">
                        <h2 style="margin-top: 5px;">
                            <i class="fa fa-video-camera"></i>
                            <?= Html::encode($session->title ?? Yii::t('BbbModule.base', 'Join session')) ?>
                        </h2>

                        <?php if ($session && $session->description): ?>
                            <div style="margin: 15px 0; color: #555; line-height: 1.6;">
                                <?= RichText::output($session->description) ?>
                            </div>
                            <hr>
                        <?php endif; ?>

                        <?php if ($msg): ?>
                            <div class="alert alert-danger"><?= Html::encode($msg) ?></div>
                        <?php endif; ?>

                        <?php if ($session): ?>
                        <div class="bbb-waiting alert alert-info" style="display: <?= $running ? 'none' : '' ?>;">
                            <i class="fa fa-spinner fa-spin"></i>
                            <?= Yii::t('BbbModule.base', 'You will be redirected when the session starts.') ?>
                        </div>
                        <div class="bbb-running" style="display: <?= $running ? '' : 'none' ?>;">
                            <div class="alert alert-success">
                                <i class="fa fa-check"></i>
                                <?= Yii::t('BbbModule.base', 'The session has started!') ?>
                            </div>

                            <?= LiveStats::widget(['session' => $session, 'running' => $running, 'cssClass' => 'mb-3']) ?>

                            <p><?= Yii::t('BbbModule.base', 'Please enter your name below:') ?></p>
                            <form method="get" action="<?= Html::encode($action) ?>" data-bbb-launch-window>
                                <div class="form-group">
                                    <label for="name"><?= Yii::t('BbbModule.base', 'Your name') ?></label>
                                    <input id="name" name="name" class="form-control input-lg" required minlength="2"
                                        maxlength="60" placeholder="<?= Yii::t('BbbModule.base', 'Your name') ?>"
                                        autofocus>
                                </div>
                                <div class="form-group mt-3">
                                    <button class="btn btn-primary btn-lg w-100" type="submit">
                                        <i class="fa fa-sign-in"></i>
                                        <?= Yii::t('BbbModule.base', 'Join now') ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>