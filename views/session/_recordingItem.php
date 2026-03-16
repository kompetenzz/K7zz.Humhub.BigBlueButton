<?php
/**
 *  Recording-Item for card-footer
 *
 * @var \k7zz\humhub\bbb\models\Recording $rec
 * @var bool   $canAdminister
 * @var \humhub\modules\content\components\ContentContainerActiveRecord $contentContainer
 */

use k7zz\humhub\bbb\models\Recording;
use yii\helpers\Html;
use yii\helpers\Url;
use humhub\modules\ui\icon\widgets\Icon;

$formats = $rec->getFormats();

// Non-admins only see published formats; skip item entirely if nothing is visible.
if (!$canAdminister && !$rec->hasAnyPublishedFormat()) {
    return;
}

$itemDomId = 'bbb-recording-' . $rec->getRecord()->getRecordId();
$publishUrlPath = Url::to(['session/publish-recording']);
$publishUrl = $this->context->contentContainer
    ? $this->context->contentContainer->createUrl($publishUrlPath)
    : Url::to($publishUrlPath);

$playTooltip = Yii::t('BbbModule.base', 'Play recording in new window');
$publishLabel = Yii::t('BbbModule.base', 'Publish');
$depublishLabel = Yii::t('BbbModule.base', 'Depublish');
$durationLabel = Yii::t('BbbModule.base', 'Duration');

$iconClock = Icon::get('clock-o');
$iconEye = Icon::get('eye');
$iconEyeSlash = Icon::get('eye-slash');
?>

<div id="<?= Html::encode($itemDomId) ?>" class="bbb-recording-item d-flex align-items-center flex-wrap gap-2 py-2"
    data-record-id="<?= Html::encode($rec->getRecord()->getRecordId()) ?>">

    <span class="text-muted small">
        <b><?= Html::encode($rec->getDate()) ?>, <?= Html::encode($rec->getTime()) ?></b>
        <br><span title="<?= Html::encode($durationLabel) ?>">
            <?= $iconClock ?> <?= Html::encode($rec->getDuration()) ?>
        </span>
    </span>

    <span class="d-inline-flex gap-1 flex-wrap align-items-center">
        <?php foreach ($formats as $format):
            $isPublished = $rec->isFormatPublished($format);
            if (!$canAdminister && !$isPublished)
                continue;
            $formatType = $format->getType();
            $formatDomId = $itemDomId . '-fmt-' . Html::encode($formatType);
            ?>
            <span id="<?= $formatDomId ?>" class="d-inline-flex align-items-center gap-1">
                <a href="<?= Html::encode($rec->getFormatUrl($format)) ?>" class="btn btn-outline-primary btn-sm"
                    target="_blank"
                    title="<?= Html::encode(Recording::formatLabel($formatType)) ?> – <?= Html::encode($playTooltip) ?>">
                    <i class="fa <?= Recording::formatIcon($formatType) ?>"></i>
                    <?= Html::encode(Recording::formatLabel($formatType)) ?>
                </a>

                <?php if ($canAdminister): ?>
                    <?= Html::beginForm($publishUrl, 'post', [
                        'class' => 'd-inline bbb-publish-form',
                        'data-async' => '1',
                        'data-fmt' => Html::encode($formatType),
                        'data-dom' => $formatDomId,
                    ]) ?>
                    <?= Html::hiddenInput('recordId', $rec->getRecord()->getRecordId()) ?>
                    <?= Html::hiddenInput('formatType', $formatType) ?>
                    <?= Html::hiddenInput('publish', $isPublished ? 'false' : 'true') ?>
                    <?= Html::submitButton($isPublished ? $iconEyeSlash : $iconEye, [
                        'class' => 'btn btn-sm ' . ($isPublished ? 'btn-success' : 'btn-warning'),
                        'title' => $isPublished ? $depublishLabel : $publishLabel,
                        'encode' => false,
                    ]) ?>
                    <?= Html::endForm() ?>
                <?php endif; ?>
            </span>
        <?php endforeach; ?>

        <?php if ($canAdminister && empty($formats)): ?>
            <span class="text-muted small"><?= Yii::t('BbbModule.base', 'No playback available') ?></span>
        <?php endif; ?>
    </span>
</div>

<?php
$iconEyeJs = addslashes($iconEye);
$iconEyeSlashJs = addslashes($iconEyeSlash);

$js = <<<JS
;(function(){
  var root = $('#{$itemDomId}');
  if (!root.length) return;
  var client = humhub.require('client');

  root.find('form.bbb-publish-form[data-async="1"]').off('submit').on('submit', function(e){
    e.preventDefault();
    var form    = $(this);
    var btn     = form.find('button[type="submit"], input[type="submit"]');
    var data    = form.serialize();
    var url     = form.attr('action');
    var fmtDom  = form.data('dom');
    btn.prop('disabled', true);

    client.post(url, { data: data })
      .then(function(resp){
        btn.prop('disabled', false);
        if (!resp || resp.status != 200) {
          var msg = (resp && resp.message) || 'Error';
          humhub.modules.ui.notification && humhub.modules.ui.notification.show(msg, {type:'danger'});
          return;
        }

        var publishField = form.find('input[name="publish"]');
        var wasPublic    = (publishField.val() === 'true');

        if (wasPublic) {
          publishField.val('false');
          btn.removeClass('btn-success').addClass('btn-warning')
             .attr('title', '{$depublishLabel}')
             .html('{$iconEyeSlashJs}');
        } else {
          publishField.val('true');
          btn.removeClass('btn-warning').addClass('btn-success')
             .attr('title', '{$publishLabel}')
             .html('{$iconEyeJs}');
        }

        humhub.modules.ui.notification && humhub.modules.ui.notification.show(resp.message || 'OK', {type:'success'});
      })
      .catch(function(e){
        console.error('Request failed:', e);
        humhub.modules.ui.notification && humhub.modules.ui.notification.show('Request failed', {type:'danger'});
      });
  });
})();
JS;

$this->registerJs($js, \yii\web\View::POS_READY);
