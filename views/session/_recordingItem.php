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

if (!$rec->isPublished() && !$canAdminister) {
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

$iconClock = Icon::get('clock');
$iconEye = Icon::get('eye');
$iconEyeSlash = Icon::get('eye-slash');

$formats = $rec->getFormats();
?>

<div id="<?= Html::encode($itemDomId) ?>" class="bbb-recording-item d-flex align-items-center flex-wrap gap-2 py-2"
    data-record-id="<?= Html::encode($rec->getRecord()->getRecordId()) ?>">

    <span class="text-muted small">
        <b><?= Html::encode($rec->getDate()) ?>, <?= Html::encode($rec->getTime()) ?></b>
        <span title="<?= Html::encode($durationLabel) ?>">
            <?= $iconClock ?> <?= Html::encode($rec->getDuration()) ?>
        </span>
    </span>

    <span class="d-inline-flex gap-1 flex-wrap">
        <?php foreach ($formats as $format): ?>
            <a href="<?= Html::encode($format->getUrl()) ?>"
               class="btn btn-outline-primary btn-sm"
               target="_blank"
               title="<?= Html::encode(Recording::formatLabel($format->getType())) ?> – <?= Html::encode($playTooltip) ?>">
                <i class="fa <?= Recording::formatIcon($format->getType()) ?>"></i>
                <?= Html::encode(Recording::formatLabel($format->getType())) ?>
            </a>
        <?php endforeach; ?>
        <?php if (empty($formats)): ?>
            <span class="text-muted small"><?= Yii::t('BbbModule.base', 'No playback available') ?></span>
        <?php endif; ?>
    </span>

    <?php if ($canAdminister): ?>
        <?= Html::beginForm($publishUrl, 'post', [
            'class' => 'd-inline bbb-publish-form',
            'data-async' => '1',
        ]) ?>
        <?= Html::hiddenInput('recordId', $rec->getRecord()->getRecordId()) ?>
        <?= Html::hiddenInput('publish', $rec->isPublished() ? 'false' : 'true') ?>
        <?= Html::submitButton($rec->isPublished() ? $iconEyeSlash : $iconEye, [
            'class' => 'btn btn-sm ' . ($rec->isPublished() ? 'btn-success' : 'btn-warning'),
            'title' => $rec->isPublished() ? $depublishLabel : $publishLabel,
            'encode' => false,
        ]) ?>
        <?= Html::endForm() ?>
    <?php endif; ?>
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
