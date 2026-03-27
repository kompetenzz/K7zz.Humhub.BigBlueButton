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

$bundle = BBBAssets::register($this);
$this->setPageTitle($session->title);
$imageUrl = $session->outputImage
    ? $session->outputImage->getUrl()
    : $bundle->baseUrl . '/images/conference.png';
?>
<div id="layout-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
                <div class="card">
                    <?= $this->renderFile('@bbb/views/session/_sessionDetails.php', [
                        'model'    => $session,
                        'running'  => false,
                        'imageUrl' => $imageUrl,
                        'top'      => true,
                    ]) ?>

                    <div class="card-body">
                        <div id="bbb-waiting-alert" class="alert alert-info">
                            <i class="fa fa-spinner fa-spin"></i>
                            <?= Yii::t('BbbModule.base', 'The session has not started yet.') ?>
                        </div>

                        <div id="bbb-join-ready" style="display:none;">
                            <div class="alert alert-success">
                                <i class="fa fa-check"></i>
                                <?= Yii::t('BbbModule.base', 'The session has started!') ?>
                            </div>
                            <a href="#" class="btn btn-success btn-lg w-100 bbb-open-window"
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
<script <?= Html::nonce() ?>>
    (function poll() {
        setTimeout(function () {
            fetch(<?= json_encode($isRunningUrl) ?>)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.running) {
                        document.getElementById('bbb-waiting-alert').style.display = 'none';
                        document.getElementById('bbb-join-ready').style.display = '';
                    } else {
                        poll();
                    }
                })
                .catch(function () { poll(); });
        }, 5000);
    })();

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.bbb-open-window');
        if (btn) {
            e.preventDefault();
            window.open(btn.dataset.url, '_blank');
        }
    });
</script>
