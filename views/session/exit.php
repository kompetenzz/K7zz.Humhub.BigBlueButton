<?php
$this->registerCss(<<<CSS
.bbb-exit-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 9999;
    background: rgba(255, 255, 255, 0.95);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    font-size: 1.2em;
    color: #333;
}
CSS);

$this->registerJs(<<<JS
document.getElementById('bbb-exit-close').addEventListener('click', function() {
    window.close();
});

setTimeout(() => {
    window.close();
}, 5000);
JS);
?>

<div class="bbb-exit-overlay">
    <h2><?= Yii::t('BbbModule.base', 'The meeting has ended.') ?></h2>
    <p><?= Yii::t('BbbModule.base', 'You can now close this window.') ?></p>
    <button id="bbb-exit-close" class="btn btn-primary mt-3">
        <?= Yii::t('BbbModule.base', 'Close now') ?>
    </button>
</div>