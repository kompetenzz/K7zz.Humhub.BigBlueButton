<?php
use yii\helpers\Json;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $model \k7zz\humhub\bbb\models\Session */
/* @var $contentContainer \humhub\modules\content\components\ContentContainerActiveRecord|null */

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

<div id="bbb-recordingsbox-<?= $model->id ?>" class="card-footer" style="display:none;">
    <div class="bbb-recordings-container"></div>
</div>

<?php
$id       = $model->id;
$countJson = Json::htmlEncode($countUrl);
$recsJson  = Json::htmlEncode($recordingsUrl);
$errJson   = Json::htmlEncode($errTxt);

$this->registerJs(<<<JS
    $.ajax({ url: {$countJson}, timeout: 10000 })
        .done(function(count) {
            if (count <= 0) return;
            var box = $('#bbb-recordingsbox-{$id}');
            box.show();
            $.ajax({ url: {$recsJson}, timeout: 10000 })
                .done(function(html) { box.find('.bbb-recordings-container').html(html); })
                .fail(function() { box.find('.bbb-recordings-container').html('<div class="text-danger">' + {$errJson} + '</div>'); });
        });
JS, \yii\web\View::POS_READY);
?>
