<?php

namespace k7zz\humhub\bbb\widgets;

use humhub\components\Widget;
use yii\widgets\ActiveForm;

class FilePreviewField extends Widget
{
    public ActiveForm $form;
    public object $model;
    public string $attribute = '';
    public ?string $removeAttr = null;
    public ?object $preview = null;
    public ?object $file = null;
    public string $label = '';
    public string $changeLabel = '';
    public string $hint = '';
    public string $maxHeight = '200px';

    public function run(): string
    {
        return $this->render('filePreviewField', [
            'f'          => $this->form,
            'model'      => $this->model,
            'attribute'  => $this->attribute,
            'removeAttr' => $this->removeAttr,
            'preview'    => $this->preview,
            'file'       => $this->file,
            'inputLabel' => ($this->preview !== null && $this->changeLabel !== '')
                ? $this->changeLabel
                : $this->label,
            'hint'       => $this->hint,
            'maxHeight'  => $this->maxHeight,
        ]);
    }
}
