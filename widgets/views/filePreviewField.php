<?php
/**
 * @var \yii\widgets\ActiveForm $f
 * @var object $model
 * @var string $attribute
 * @var string|null $removeAttr
 * @var object|null $preview  FileRecord with getUrl()
 * @var object|null $file     FileRecord with file_name/size
 * @var string $inputLabel
 * @var string $hint
 * @var string $maxHeight
 */

use yii\helpers\Html;
$maxHeight ??= '200px';

$inputId = Html::getInputId($model, $attribute);
?>
<div class="form-group bbb-file-preview-field">
    <div class="mt-2 p-2">
        <div class="row align-items-start">
            <div class="col-md-6">
                <?php if ($inputLabel !== ''): ?>
                    <label for="<?= Html::encode($inputId) ?>" class="control-label">
                        <?= Html::encode($inputLabel) ?>
                    </label>
                <?php endif; ?>
                <?php if ($hint !== ''): ?>
                    <div class="hint-block">
                        <?= Html::encode($hint) ?>
                    </div>
                <?php endif; ?>
                <?php if ($file !== null): ?>
                    <div class="small text-muted mb-2">
                        <?= Html::encode($file->file_name) ?>
                        (<?= round($file->size / 1024 / 1024, 2) ?>MB)
                    </div>
                <?php endif; ?>
                <?= $f->field($model, $attribute, ['template' => '{input}{error}'])->fileInput() ?>
                <?php if ($preview !== null && $removeAttr !== null): ?>
                    <?= $f->field($model, $removeAttr)
                        ->checkbox(['label' => Yii::t('BbbModule.base', 'Remove')])
                        ->label(false) ?>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <?php if ($preview !== null): ?>
                    <img src="<?= Html::encode($preview->getUrl()) ?>" class="img-fluid img-thumbnail d-block"
                        style="max-height: <?= Html::encode($maxHeight) ?>; max-width: 100%;" alt="">
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>