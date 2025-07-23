<?php
use yii\helpers\Html;
use yii\web\View;

/** @var string $ajaxUrl */
/** @var string $sessionId */

$containerId = 'bbb-recordings-' . $sessionId;
?>

<div id="<?= $containerId ?>" class="bbb-recording-container">
</div>

<?php
$text = Yii::t('BbbModule.base', 'Recordings');
$emptyText = Yii::t("BbbModule.base", "No recordings available");
$durationLabel = Yii::t("BbbModule.base", "Duration");

$this->registerJs(<<<JS
$.getJSON('{$ajaxUrl}', function(data) {
    var container = $('#$containerId');
    container.empty();

    if (!data.length) {
        container.append('<p>{$emptyText}</p>');
        return;
    }
    container.append('<h6>{$text}</h6>');

    data.forEach(function(rec) {
        var html = `
            <div>
                <a href="\${rec . url}" target="_blank" class="">▶️ </a> 
                \${rec . date}, \${rec . time}, \${rec . duration}
            </div>
        `;
        container.append(html);
    });
});
JS, View::POS_READY);
?>