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

if (!$rec->isPublished() && !$canAdminister) {
    // Don't show not-published recordings to non-admins
    return;
}

$itemDomId = 'bbb-recording-' . $rec->getRecord()->getRecordId();
$publishUrlPath = Url::to(['session/publish-recording']);
$publishUrl = $this->context->contentContainer
    ? $this->context->contentContainer->createUrl($publishUrlPath)
    : Url::to($publishUrlPath);

$playTooltip = Yii::t('BbbModule.base', 'Play recording in new window');
$durationLabel = Yii::t('BbbModule.base', 'Duration');
$publishLabel = Yii::t('BbbModule.base', 'Publish recording');
$depublishLabel = Yii::t('BbbModule.base', 'Depublish recording');

$iconVideo = Icon::get('video');
$iconClock = Icon::get('clock');
$iconOpen = Icon::get('external-link');
$iconEye = Icon::get('eye');
$iconEyeSlash = Icon::get('eye-slash');

// Mini-Thumb (sehr klein im Footer)
$thumbHtml = '';
if ($rec->hasImagePreviews()) {
    $p = $rec->getImagePreviews()[0];
    $thumbHtml = Html::img($p->getUrl(), [
        'alt' => $p->getAlt() ?? ($rec->getRecord()->getName() ?? 'Recording'),
        'class' => 'rounded border',
        'style' => 'width:80%;object-fit:cover;',
    ]);
} else {
    $thumbHtml = Html::tag('span', $iconVideo);
}
?>
<!-- ROOT-ZEILE: eine Zeile, Buttons rechts -->
<div id="<?= Html::encode($itemDomId) ?>" class="bbb-recording-item d-flex align-items-center flex-nowrap py-1"
    data-record-id="<?= Html::encode($rec->getRecord()->getRecordId()) ?>">

    <span class="text-muted small ml-2 ms-2">
        <b><?= Html::encode($rec->getDate()) ?>, <?= Html::encode($rec->getTime()) ?></b> •
        <span title="<?= Html::encode($durationLabel) ?>">
            <?= $iconClock ?> <?= Html::encode($rec->getDuration()) ?>
        </span>
    </span>

    <div>
        <?php if ($rec->getUrl()): ?>
            <a href="<?= Html::encode($rec->getUrl()) ?>" class="text-reset text-decoration-none" target="_blank"
                title="<?= Html::encode($playTooltip) ?>">
                <?= $thumbHtml ?>
            </a>
        <?php else: ?>
            <span class="text-muted text-truncate d-inline-block" style="max-width:100%;">
                <?= $thumbHtml ?>
            </span>
        <?php endif; ?>
        <?php if ($canAdminister): ?>
            <?= Html::beginForm($publishUrl, 'post', [
                'class' => 'm-0 p-0 d-block bbb-publish-form pull-right ',
                'data-async' => '1',
            ]) ?>
            <?= Html::hiddenInput('recordId', $rec->getRecord()->getRecordId()) ?>
            <?= Html::hiddenInput('publish', $rec->isPublished() ? 'false' : 'true') ?>
            <?= Html::submitButton($rec->isPublished() ? $iconEyeSlash : $iconEye, [
                'class' => 'btn btn-success btn-sm',
                'title' => $rec->isPublished() ? $depublishLabel : $publishLabel,
                'encode' => false,
            ]) ?>
            <?= Html::endForm() ?>
        <?php endif; ?>
    </div>
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
        var form = $(this);
        var btn  = form.find('button[type="submit"], input[type="submit"]');
        var data = form.serialize();
        var url  = form.attr('action'); 
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
            var wasPublic = (publishField.val() === 'true');

            if (wasPublic) {
              publishField.val('false');
              btn.removeClass('btn-success').addClass('btn-warning')
                 .attr('title','{$depublishLabel}')
                 .html('{$iconEyeSlashJs}');
            } else {
              publishField.val('true');
              btn.removeClass('btn-warning').addClass('btn-success')
                 .attr('title','{$publishLabel}')
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
