<?php
/**
 *  Recording-Item for card-footer
 *
 * @var \k7zz\humhub\bbb\models\Recording $rec
 * @var bool   $canAdminister
 * @var int    $sessionId
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
$publishUrl = $this->context->contentContainer
    ? $this->context->contentContainer->createUrl('/bbb/session/publish-recording')
    : Url::to(['/bbb/session/publish-recording']);
$deleteUrl = $this->context->contentContainer
    ? $this->context->contentContainer->createUrl('/bbb/session/delete-recording')
    : Url::to(['/bbb/session/delete-recording']);

$playTooltip = Yii::t('BbbModule.base', 'Play recording in new window');
$publishLabel = Yii::t('BbbModule.base', 'Publish');
$depublishLabel = Yii::t('BbbModule.base', 'Depublish');
$deleteRecordingLabel = Yii::t('BbbModule.base', 'Delete recording');
$deleteConfirmLabel = Yii::t('BbbModule.base', 'This will permanently delete the recording on BBB and cannot be undone. Continue?');
$durationLabel = Yii::t('BbbModule.base', 'Duration');
$noRecordingsLabel = Yii::t('BbbModule.base', 'No recordings available');

$iconClock = Icon::get('clock-o');
$iconEye = Icon::get('eye');
$iconEyeSlash = Icon::get('eye-slash');
$iconTrash = Icon::get('trash');
?>

<div id="<?= Html::encode($itemDomId) ?>" class="bbb-recording-item py-2"
    data-record-id="<?= Html::encode($rec->getRecord()->getRecordId()) ?>">
    <div class="d-flex justify-content-between align-items-start gap-2">
        <span class="text-muted small">
            <b><?= Html::encode($rec->getDate()) ?>, <?= Html::encode($rec->getTime()) ?></b>
            <br><span title="<?= Html::encode($durationLabel) ?>">
                <?= $iconClock ?> <?= Html::encode($rec->getDuration()) ?>
            </span>
        </span>

        <?php if ($canAdminister): ?>
            <?= Html::beginForm($deleteUrl, 'post', [
                'class' => 'd-inline bbb-delete-recording-form',
                'data-async' => '1',
                'data-confirm-delete' => $deleteConfirmLabel,
            ]) ?>
            <?= Html::hiddenInput('id', $sessionId) ?>
            <?= Html::hiddenInput('recordId', $rec->getRecord()->getRecordId()) ?>
            <?= Html::submitButton($iconTrash, [
                'class' => 'btn btn-danger btn-sm bbb-delete-recording-btn',
                'title' => $deleteRecordingLabel,
                'encode' => false,
            ]) ?>
            <?= Html::endForm() ?>
        <?php endif; ?>
    </div>

    <span class="d-inline-flex gap-1 flex-wrap align-items-center mt-2">
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
                    <?= Html::hiddenInput('id', $sessionId) ?>
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
$noRecordingsLabelJs = addslashes($noRecordingsLabel);

$css = <<<CSS
.bbb-recording-item .bbb-delete-recording-btn {
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.15s ease;
}

.bbb-recording-item:hover .bbb-delete-recording-btn,
.bbb-recording-item:focus-within .bbb-delete-recording-btn {
    opacity: 1;
    visibility: visible;
}
CSS;

$this->registerCss($css);

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

    root.find('form.bbb-delete-recording-form[data-async="1"]').off('submit').on('submit', function(e){
        e.preventDefault();
        var form = $(this);
        var confirmText = form.data('confirm-delete') || 'Delete?';
        if (!window.confirm(confirmText)) {
            return;
        }

        var btn = form.find('button[type="submit"], input[type="submit"]');
        btn.prop('disabled', true);

        client.post(form.attr('action'), { data: form.serialize() })
            .then(function(resp){
                btn.prop('disabled', false);
                if (!resp || resp.status != 200) {
                    var msg = (resp && resp.message) || 'Error';
                    humhub.modules.ui.notification && humhub.modules.ui.notification.show(msg, {type:'danger'});
                    return;
                }

                root.remove();

                var list = $('.bbb-recordings-container').first();
                if (list.length && list.find('.bbb-recording-item').length === 0) {
                    list.html('<p class="text-muted">{$noRecordingsLabelJs}</p>');
                }

                humhub.modules.ui.notification && humhub.modules.ui.notification.show(resp.message || 'OK', {type:'success'});
            })
            .catch(function(err){
                btn.prop('disabled', false);
                console.error('Delete failed:', err);
                humhub.modules.ui.notification && humhub.modules.ui.notification.show('Request failed', {type:'danger'});
            });
    });
})();
JS;

$this->registerJs($js, \yii\web\View::POS_READY);
