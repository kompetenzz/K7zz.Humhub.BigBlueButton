<?php
/**
 * Partial: Compact Recording-entry Bootstrap card-footer
 *
 * Erwartet:
 * @var object $rec            // Methoden: getRecord()->getRecordId(), getUrl(), hasImagePreviews(), getImagePreviews(), getDate(), getTime(), getDuration(), isPublished(), title?
 * @var bool   $canAdminister
 */

use yii\helpers\Html;
use yii\helpers\Url;
use humhub\modules\ui\icon\widgets\Icon;

$itemDomId = 'bbb-recording-' . $rec->getRecord()->getRecordId();
$publishUrlPath = Url::to(['session/publish-recording']);
$publishUrl = $this->context->contentContainer
    ? $this->context->contentContainer->createUrl($publishUrlPath)
    : Url::to($publishUrlPath);

$playTooltip = Yii::t('BbbModule.base', 'Play recording in new window');
$durationLabel = Yii::t('BbbModule.base', 'Duration');
$openLabel = Yii::t('BbbModule.base', 'Open');
$publishedBadge = Yii::t('BbbModule.base', 'Published');
$unpublishedBadge = Yii::t('BbbModule.base', 'Hidden');
$publishLabel = Yii::t('BbbModule.base', 'Publish recording');
$depublishLabel = Yii::t('BbbModule.base', 'Depublish recording');
$confirmPublish = Yii::t('BbbModule.base', 'Are you sure you want to publish this recording?');
$confirmDepub = Yii::t('BbbModule.base', 'Are you sure you want to depublish this recording?');

$iconVideo = Icon::get('video');
$iconPlay = Icon::get('play');
$iconClock = Icon::get('clock');
$iconOpen = Icon::get('external-link');
$iconEye = Icon::get('eye');
$iconEyeSlash = Icon::get('eye-slash');

// Mini-Vorschau (falls vorhanden) sehr klein für Footer
$thumbHtml = '';
if ($rec->hasImagePreviews()) {
    $preview = $rec->getImagePreviews()[0];
    $thumbHtml = Html::img(
        $preview->getUrl(),
        [
            'alt' => $preview->getAlt() ?? ($rec->getRecord()->getName() ?? 'Recording'),
            'class' => 'rounded border flex-shrink-0',
            'style' => 'width:56px;height:18px;object-fit:cover;'
        ]
    );
} else {
    $thumbHtml = Html::tag('span', $iconVideo, ['class' => 'flex-shrink-0']);
}
?>

<div id="<?= Html::encode($itemDomId) ?>"
    class="bbb-recording-item d-flex align-items-center justify-content-between gap-2 py-1"
    data-publish-url="<?= Html::encode($publishUrl) ?>"
    data-record-id="<?= Html::encode($rec->getRecord()->getRecordId()) ?>">

    <!-- Links: Mini-Thumb + Titel + Meta -->
    <div class="d-flex align-items-center flex-grow-1 overflow-hidden" style="min-width:0;">
        <div class="mr-2 me-2 flex-shrink-0">
            <?= $thumbHtml ?>
        </div>

        <div class="text-truncate" style="min-width:0;">
            <?php if ($rec->getUrl()): ?>
                <a href="<?= Html::encode($rec->getUrl()) ?>" class="text-reset text-decoration-none" target="_blank"
                    title="<?= Html::encode($playTooltip) ?>">
                    <span class="mr-1 me-1"><?= $iconPlay ?></span>
                    <span class="text-truncate d-inline-block" style="max-width:100%;">
                        <?= Html::encode($rec->title ?? Yii::t('BbbModule.base', 'Recording')) ?>
                    </span>
                </a>
            <?php else: ?>
                <span class="text-muted text-truncate d-inline-block" style="max-width:100%;">
                    <?= Html::encode($rec->title ?? Yii::t('BbbModule.base', 'Recording')) ?>
                </span>
            <?php endif; ?>

            <span class="text-muted small ml-2 ms-2">
                <?= Html::encode($rec->getDate()) ?>, <?= Html::encode($rec->getTime()) ?> •
                <span title="<?= Html::encode($durationLabel) ?>">
                    <?= $iconClock ?> <?= Html::encode($rec->getDuration()) ?>
                </span>
            </span>
        </div>

        <span
            class="bbb-recording-state badge ml-2 ms-2 flex-shrink-0 <?= $rec->isPublished() ? 'bg-success' : 'bg-secondary' ?>">
            <?= Html::encode($rec->isPublished() ? $publishedBadge : $unpublishedBadge) ?>
        </span>
    </div>

    <!-- RECHTS: Actions; bleiben immer rechts, nicht umbrechen -->
    <div class="d-flex align-items-center ml-auto ms-auto" style="white-space:nowrap; flex:0 0 auto;">
        <?php if ($rec->getUrl()): ?>
            <a href="<?= Html::encode($rec->getUrl()) ?>" target="_blank"
                class="btn btn-outline-primary btn-sm py-0 mr-1 me-1" title="<?= Html::encode($openLabel) ?>">
                <?= $iconOpen ?>
            </a>
        <?php endif; ?>
    </div>
</div>

<?php
// Kleines, itemspezifisches JS für Publish/Depublish (kompakt & footer-tauglich)
$js = <<<JS
;(function(){
  var root = $('#{$itemDomId}');
  if (!root.length) return;

  root.find('.bbb-publish-toggle').off('click').on('click', function(e){
    e.preventDefault();
    var btn = $(this);
    var publishUrl = root.data('publish-url');
    var recordId   = root.data('record-id');
    var publish    = String(btn.data('publish')) === 'true';
    var confirmMsg = btn.data('confirm');
    if (confirmMsg && !confirm(confirmMsg)) return;

    btn.prop('disabled', true);
    $.post(publishUrl, {recordId: recordId, publish: publish, _csrf: yii.getCsrfToken()})
      .done(function(resp){
        if (!resp || !resp.success) {
          var msg = (resp && resp.message) || 'Error';
          humhub.modules.ui.notification && humhub.modules.ui.notification.show(msg, {type:'danger'});
          return;
        }
        var badge = root.find('.bbb-recording-state');
        if (publish) {
          badge.removeClass('bg-secondary').addClass('bg-success').text('{$publishedBadge}');
          btn.removeClass('btn-success').addClass('btn-warning')
             .data('publish', false)
             .attr('title','{$depublishLabel}')
             .html('{$iconEyeSlash}');
        } else {
          badge.removeClass('bg-success').addClass('bg-secondary').text('{$unpublishedBadge}');
          btn.removeClass('btn-warning').addClass('btn-success')
             .data('publish', true)
             .attr('title','{$publishLabel}')
             .html('{$iconEye}');
        }
        humhub.modules.ui.notification && humhub.modules.ui.notification.show(resp.message || 'OK', {type:'success'});
      })
      .fail(function(){
        humhub.modules.ui.notification && humhub.modules.ui.notification.show('Request failed', {type:'danger'});
      })
      .always(function(){ btn.prop('disabled', false); });
  });
})();
JS;
$this->registerJs($js, \yii\web\View::POS_READY);
