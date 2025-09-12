<?php
/**
 *  Recording-Item for card-footer
 *
 * @var object $rec
 * @var bool   $canAdminister
 * @var \humhub\modules\content\components\ContentContainerActiveRecord $contentContainer
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
$iconClock = Icon::get('clock');
$iconOpen = Icon::get('external-link');
$iconEye = Icon::get('eye');
$iconEyeSlash = Icon::get('eye-slash');

// Play-Glyph (Fallback statt Icon::get('play'))
$playGlyph = Html::tag('span', '▶', ['class' => 'text-muted mr-1 me-1', 'aria-hidden' => 'true']);

// Mini-Thumb (sehr klein im Footer)
$thumbHtml = '';
if ($rec->hasImagePreviews()) {
    $p = $rec->getImagePreviews()[0];
    $thumbHtml = Html::img($p->getUrl(), [
        'alt' => $p->getAlt() ?? ($rec->getRecord()->getName() ?? 'Recording'),
        'class' => 'rounded border',
        'style' => 'width:28px;height:18px;object-fit:cover;',
    ]);
} else {
    $thumbHtml = Html::tag('span', $iconVideo);
}
?>
<!-- ROOT-ZEILE: eine Zeile, Buttons rechts -->
<div id="<?= Html::encode($itemDomId) ?>" class="bbb-recording-item d-flex align-items-center flex-nowrap py-1"
    data-record-id="<?= Html::encode($rec->getRecord()->getRecordId()) ?>">

    <!-- LINKS: Thumbnail + Text (darf schrumpfen) -->
    <div class="d-flex align-items-center flex-grow-1 overflow-hidden" style="min-width:0;">
        <div class="mr-2 me-2 flex-shrink-0">
            <?= $thumbHtml ?>
        </div>

        <div class="text-truncate" style="min-width:0;">
            <?php if ($rec->getUrl()): ?>
                <a href="<?= Html::encode($rec->getUrl()) ?>" class="text-reset text-decoration-none" target="_blank"
                    title="<?= Html::encode($playTooltip) ?>">
                    <?= $playGlyph ?>
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

    <!-- RECHTS: Actions (POST-Forms; optional per JS async) -->
    <div class="d-flex align-items-center ml-auto ms-auto" style="white-space:nowrap; flex:0 0 auto;">
        <?php if ($rec->getUrl()): ?>
            <a href="<?= Html::encode($rec->getUrl()) ?>" target="_blank"
                class="btn btn-outline-primary btn-sm py-0 mr-1 me-1" title="<?= Html::encode($openLabel) ?>">
                <?= $iconOpen ?>
            </a>
        <?php endif; ?>

        <?php if ($canAdminister): ?>
            <?php if ($rec->isPublished()): ?>
                <?php // Depublish (POST) ?>
                <?= Html::beginForm($publishUrl, 'post', [
                    'class' => 'm-0 p-0 d-inline-block bbb-publish-form',
                    'data-async' => '1', // JS-Enhancement
                    'onsubmit' => "return confirm('" . Html::encode($confirmDepub) . "');"
                ]) ?>
                <?= Html::hiddenInput('recordId', $rec->getRecord()->getRecordId()) ?>
                <?= Html::hiddenInput('publish', 'false') ?>
                <?= Html::submitButton($iconEyeSlash, [
                    'class' => 'btn btn-warning btn-sm py-0',
                    'title' => $depublishLabel,
                    'encode' => false, // Icons nicht escapen
                ]) ?>
                <?= Html::endForm() ?>
            <?php else: ?>
                <?php // Publish (POST) ?>
                <?= Html::beginForm($publishUrl, 'post', [
                    'class' => 'm-0 p-0 d-inline-block bbb-publish-form',
                    'data-async' => '1',
                    'onsubmit' => "return confirm('" . Html::encode($confirmPublish) . "');"
                ]) ?>
                <?= Html::hiddenInput('recordId', $rec->getRecord()->getRecordId()) ?>
                <?= Html::hiddenInput('publish', 'true') ?>
                <?= Html::submitButton($iconEye, [
                    'class' => 'btn btn-success btn-sm py-0',
                    'title' => $publishLabel,
                    'encode' => false,
                ]) ?>
                <?= Html::endForm() ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// Optionales JS-Enhancement: macht die POST-Forms asynchron und toggelt UI ohne Reload.
// Fallback: ohne JS ist es ein normaler POST mit Seitenreload.
$publishedBadgeJs = addslashes($publishedBadge);
$unpublishedBadgeJs = addslashes($unpublishedBadge);
$iconEyeJs = addslashes($iconEye);
$iconEyeSlashJs = addslashes($iconEyeSlash);

$js = <<<JS
    ;(function(){
      var root = $('#{$itemDomId}');
      if (!root.length) return;
      var client = humhub.require('client');
    
      root.find('form.bbb-publish-form[data-async="1"]').off('submit').on('submit', function(e){
        e.preventDefault();
        var form = $(this);
        var btn  = form.find('button[type="submit"], input[type="submit"]');
        var data = form.serialize();
        var url  = form.attr('action');
        btn.prop('disabled', true);

        client.post(url, { data: data })
          .then(function(resp){
            if (!resp || !resp.success) {
              var msg = (resp && resp.message) || 'Error';
              humhub.modules.ui.notification && humhub.modules.ui.notification.show(msg, {type:'danger'});
              return;
            }
            // UI toggeln
            var badge = root.find('.bbb-recording-state');
            var publishField = form.find('input[name="publish"]');
            var wasPublish = (publishField.val() === 'true');
    
            if (wasPublish) {
              // Gerade published -> jetzt Depublish-Form anzeigen
              badge.removeClass('bg-secondary').addClass('bg-success').text('{$publishedBadgeJs}');
              // Button zu "Depublish" umbauen
              publishField.val('false');
              btn.removeClass('btn-success').addClass('btn-warning')
                 .attr('title','{$depublishLabel}')
                 .html('{$iconEyeSlashJs}');
            } else {
              // Gerade depublished -> jetzt Publish-Form anzeigen
              badge.removeClass('bg-success').addClass('bg-secondary').text('{$unpublishedBadgeJs}');
              publishField.val('true');
              btn.removeClass('btn-warning').addClass('btn-success')
                 .attr('title','{$publishLabel}')
                 .html('{$iconEyeJs}');
            }
    
            humhub.modules.ui.notification && humhub.modules.ui.notification.show(resp.message || 'OK', {type:'success'});
          })
          .catch(function(e){
            humhub.modules.ui.notification && humhub.modules.ui.notification.show('Request failed', {type:'danger'});
          });
      });
    })();
    JS;

$this->registerJs($js, \yii\web\View::POS_READY);
