<?php
/**
 * @var string $containerId
 * @var string $ajaxUrl
 * @var string $errTxt
 * @var bool $  
 *
 * @var int $sessionId
 */

use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;

// Sicher ins JS injizieren
$containerJson = Json::htmlEncode($containerId);
$ajaxUrlJson = Json::htmlEncode($ajaxUrl);
$errTxtJson = Json::htmlEncode($errTxt);

$js = <<<JS
;(function() {
    var box = $('#'+ {$containerJson});
    $.get({$ajaxUrlJson})
        .done(function(html){ box.html(html); })
        .fail(function(){ box.html('<div class="text-danger">' + {$errTxtJson} + '.</div>'); });
})();
JS;
$this->registerJs($js, View::POS_READY);
?>

<div id="<?= Html::encode($containerId) ?>" class="bbb-recordings-container">
    <div class="text-center text-muted py-3">
        <i class="fa fa-spinner fa-spin"></i>
        <?= Html::encode(Yii::t('BbbModule.base', 'Loading recordings...')) ?>
    </div>
</div>