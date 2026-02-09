<?php
use humhub\libs\Html;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\ui\icon\widgets\Icon;
use k7zz\humhub\bbb\assets\BBBAssets;

/* @var $this \yii\web\View */
/* @var $session \k7zz\humhub\bbb\models\Session */
/* @var $canStart bool */
/* @var $startUrl string */

$bundle = BBBAssets::register($this);
$imageUrl = $session->outputImage
    ? $session->outputImage->getUrl()
    : $bundle->baseUrl . '/images/conference.png';
?>
<div id="layout-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 col-md-offset-2 col-lg-6 col-lg-offset-3">
                <div class="panel panel-default">
                    <div class="panel-heading" style="padding: 0; overflow: hidden;">
                        <img src="<?= Html::encode($imageUrl) ?>"
                             alt="<?= Html::encode($session->title) ?>"
                             style="width: 100%; max-height: 300px; object-fit: cover; display: block;">
                    </div>

                    <div class="panel-body">
                        <h2 style="margin-top: 5px;">
                            <?= Icon::get('video-camera') ?>
                            <?= Html::encode($session->title) ?>
                        </h2>

                        <?php if ($session->description): ?>
                            <div style="margin: 15px 0; color: #555; line-height: 1.6;">
                                <?= RichText::output($session->description) ?>
                            </div>
                            <hr>
                        <?php endif; ?>

                        <div class="alert alert-info">
                            <i class="fa fa-spinner fa-spin"></i>
                            <?= Yii::t('BbbModule.base', 'The session has not started yet. This page will refresh automatically.') ?>
                        </div>

                        <?php if ($canStart): ?>
                            <?= Html::a(
                                Icon::get('play') . ' ' . Yii::t('BbbModule.base', 'Start session'),
                                $startUrl,
                                ['class' => 'btn btn-primary btn-lg btn-block']
                            ) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script <?= Html::nonce() ?>>
    setTimeout(() => { window.location.reload(); }, 10000);
</script>
