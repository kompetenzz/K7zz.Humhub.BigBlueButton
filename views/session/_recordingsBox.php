<?php
use yii\helpers\Json;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $model \k7zz\humhub\bbb\models\Session */
/* @var $contentContainer \humhub\modules\content\components\ContentContainerActiveRecord|null */
/* @var $chatEnabled bool */
$chatEnabled = $chatEnabled ?? true;

if (!$model->canJoin()) {
    return;
}

$countUrl = $contentContainer
    ? $contentContainer->createUrl('/bbb/session/recordings-count', ['id' => $model->id])
    : Url::to(['/bbb/session/recordings-count', 'id' => $model->id]);

$recordingsUrl = $contentContainer
    ? $contentContainer->createUrl('/bbb/session/recordings', ['id' => $model->id])
    : Url::to(['/bbb/session/recordings', 'id' => $model->id]);

$errTxt = Yii::t('BbbModule.base', 'Error loading recordings');
?>

<div id="bbb-recordings-box-<?= $model->id ?>" class="bbb-recordings-box" style="display:none;">
    <div class="bbb-panel-heading">
        <?= \humhub\modules\ui\icon\widgets\Icon::get('video-camera') ?>
        <?= Yii::t('BbbModule.base', 'Recordings') ?>
    </div>
    <div class="bbb-recordings-container<?= $chatEnabled ? '' : ' bbb-recordings-container--unlimited' ?>"></div>
</div>

<?php
$id          = $model->id;
$countJson   = Json::htmlEncode($countUrl);
$recsJson    = Json::htmlEncode($recordingsUrl);
$errJson     = Json::htmlEncode($errTxt);
$showColJs = $chatEnabled ? '' : "\$('#bbb-recordings-box-{$id}').closest('.bbb-session-right').show();";
$hideColJs = $chatEnabled ? '' : "\$('#bbb-recordings-box-{$id}').closest('.bbb-session-right').hide();";

$this->registerJs(<<<JS
    $.ajax({ url: {$countJson}, timeout: 10000 })
        .done(function(count) {
            if (count <= 0) {
                {$hideColJs}
                \$(document).trigger('bbb:layout:{$id}');
                return;
            }
            var box = \$('#bbb-recordings-box-{$id}');
            \$.ajax({ url: {$recsJson}, timeout: 10000 })
                .done(function(html) {
                    var trimmed = html.trim();
                    if (!trimmed) {
                        {$hideColJs}
                        \$(document).trigger('bbb:layout:{$id}');
                        return;
                    }
                    {$showColJs}
                    box.show();
                    box.find('.bbb-recordings-container').html(trimmed);
                    \$(document).trigger('bbb:layout:{$id}');
                })
                .fail(function() {
                    {$hideColJs}
                    \$(document).trigger('bbb:layout:{$id}');
                });
        })
        .fail(function() {
            {$hideColJs}
            \$(document).trigger('bbb:layout:{$id}');
        });
JS, \yii\web\View::POS_READY);
?>
