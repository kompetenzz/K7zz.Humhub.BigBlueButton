<?php
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\custom_pages\modules\template\models\RichtextContent;
use humhub\modules\ui\icon\widgets\Icon;
use k7zz\humhub\bbb\assets\BBBAssets;
use k7zz\humhub\bbb\widgets\RecordingsList;
use yii\helpers\Url;
use humhub\libs\Html;

/** 
 * @var k7zz\humhub\bbb\models\Session $model           The session model
 */

$bundle = BBBAssets::register(view: $this);

$imageUrl = $model->outputImage ? $model->outputImage->getUrl() : $bundle->baseUrl . '/images/conference.png';
$viewUrl = $model->content && $model->content->container
    ? $model->content->container->createUrl('/bbb/sessions')
    : Url::to('/bbb/sessions');
$viewUrl .= '?highlight=' . $model->id;

/** @var $model k7zz\humhub\bbb\models\Session */
?>
<div class="bbb-wall-entry-session">
    <div class="row">
        <div class="col-md-3">
            <img class="" alt="<?= Yii::t('BbbModule.base', 'Session image') ?>" style="max-height: 200px;"
                src="<?= Html::encode($imageUrl) ?>" />
        </div>
        <div class="col-md-9">
            <div>
                <?= RichText::output($model->description) ?>
            </div>
            <div class="footer">
                <?= Html::a(
                    Icon::get('eye') . ' ' . Yii::t('BbbModule.base', 'View'),
                    $viewUrl,
                    [
                        'class' => 'btn btn-secondary btn-sm',
                        'title' => Yii::t('BbbModule.base', 'View session details'),
                    ]
                ) ?>
            </div>
        </div>
    </div>
</div>