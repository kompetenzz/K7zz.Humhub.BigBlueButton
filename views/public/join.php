<?php
use humhub\libs\Html;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $title string */
/* @var $token string */
/* @var $msg string|null */
/* @var $reload bool|null */
$action = Url::to(['/bbb/public/join', 'token' => $token]);
?>
<h3><?= Yii::t('BbbModule.base', 'Join session') ?></h3>
<h2><?= $title ?></h2>
<?php if ($msg): ?>
    <div class="alert alert-danger"><?= Html::encode($msg) ?></div>
    <?php if ($reload): ?>
        <div class="alert alert-info"><?= Yii::t('BbbModule.base', 'You will be redirected when the session starts.') ?></div>
        <script <?= Html::nonce() ?>>
            setTimeout(() => {
                window.location.reload();
            }, 5000);
        </script>
    <?php endif; ?>
<?php else: ?>
    <p><?= Yii::t('BbbModule.base', 'Please enter your name below:') ?></p>
    <form method="get" action="<?= Html::encode($action) ?>">
        <input type="hidden" name="token" value="<?= Html::encode($token) ?>">
        <div class="form-group">
            <label for="name"><?= Yii::t('BbbModule.base', 'Your name') ?></label>
            <input id="name" name="name" class="form-control" required minlength="2" maxlength="60">
        </div>
        <button class="btn btn-primary"><?= Yii::t('BbbModule.base', 'Join now') ?></button>
    </form>
<?php endif; ?>