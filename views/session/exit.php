<?php
$this->registerJs(<<<JS
    document.getElementById('bbb-exit-close').addEventListener('click', function() {
        window.close();
    });

    // Optional: versuche Auto-Close (nur wenn vorher per window.open() geöffnet)
    setTimeout(() => {
        window.close();
    }, 5000);
JS);
?>

<div style="text-align: center; padding: 2em;">
    <h2><?= Yii::t('BbbModule.base', 'The meeting has ended.') ?></h2>
    <p><?= Yii::t('BbbModule.base', 'You can now close this window.') ?></p>
    <button id="bbb-exit-close" class="btn btn-primary">
        <?= Yii::t('BbbModule.base', 'Close now') ?>
    </button>
</div>