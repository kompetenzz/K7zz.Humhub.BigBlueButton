<?php
use humhub\libs\Html;
use humhub\modules\content\widgets\richtext\RichText;
use k7zz\humhub\bbb\assets\BBBAssets;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $session \k7zz\humhub\bbb\models\Session|null */
/* @var $token string */
/* @var $msg string|null */
/* @var $reload bool|null */

$bundle = BBBAssets::register($this);
$action = Url::to(['/bbb/public/join', 'token' => $token]);
$imageUrl = ($session && $session->outputImage)
    ? $session->outputImage->getUrl()
    : $bundle->baseUrl . '/images/conference.png';
?>
<div id="layout-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
                <div class="card">
                    <?php if ($session): ?>
                        <div class="card-header" style="padding: 0; overflow: hidden;">
                            <img src="<?= Html::encode($imageUrl) ?>"
                                 alt="<?= Html::encode($session->title) ?>"
                                 style="width: 100%; max-height: 300px; object-fit: cover; display: block;">
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
                            <?php if ($reload): ?>
                                <div class="alert alert-info">
                                    <i class="fa fa-spinner fa-spin"></i>
                                    <?= Yii::t('BbbModule.base', 'You will be redirected when the session starts.') ?>
                                </div>
                                <script <?= Html::nonce() ?>>
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 10000);
                                </script>
                            <?php endif; ?>
                        <?php else: ?>
                            <p><?= Yii::t('BbbModule.base', 'Please enter your name below:') ?></p>
                            <form method="get" action="<?= Html::encode($action) ?>">
                                <input type="hidden" name="token" value="<?= Html::encode($token) ?>">
                                <div class="form-group">
                                    <label for="name"><?= Yii::t('BbbModule.base', 'Your name') ?></label>
                                    <input id="name" name="name" class="form-control input-lg"
                                           required minlength="2" maxlength="60"
                                           placeholder="<?= Yii::t('BbbModule.base', 'Your name') ?>"
                                           autofocus>
                                </div>
                                <button class="btn btn-primary btn-lg w-100">
                                    <i class="fa fa-sign-in"></i>
                                    <?= Yii::t('BbbModule.base', 'Join now') ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
