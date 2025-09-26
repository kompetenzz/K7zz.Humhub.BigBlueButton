<?php
/**
 * View: List and manage BBB sessions for a container or globally.
 *
 * Variables:
 * @var array $rows           List of session data (model, running)
 * @var int $highlightId      ID of the session to highlight (optional)
 * @var \humhub\modules\content\components\ContentContainerActiveRecord|null $contentContainer
 */

use humhub\libs\Html;
use k7zz\humhub\bbb\widgets\SessionCard;
use k7zz\humhub\bbb\permissions\Admin;
use humhub\modules\ui\icon\widgets\Icon;
use yii\helpers\Url;
$createUrl = $this->context->contentContainer
    ? $this->context->contentContainer->createUrl('/bbb/session/create')
    : Url::to('/bbb/session/create');

$canCreate = $this->context->contentContainer
    ? $this->context->contentContainer->can(Admin::class)
    : Yii::$app->user->can(Admin::class);

?>
<div class="container-fluid container-cards container-bbb-sessions">
    <div class="panel panel-default">
        <div class="panel-heading">
            <?php if ($canCreate) { ?>
                <?= Html::a(
                    Icon::get('plus') . ' ' . Yii::t('BbbModule.base', 'Create session'),
                    $createUrl,
                    ['class' => 'btn btn-primary pull-right', 'style' => 'margin: 10px']
                ); ?>
            <?php } ?>
            <strong><?= Yii::t('BbbModule.base', 'Conference sessions') ?></strong>
        </div>

        <div class="panel-body">
            <p>
                <?= Yii::t('BbbModule.base', 'Here you can manage your conference sessions.'); ?>
            </p>
        </div>
    </div>
    <?php if (empty($rows)) { ?>
        <div class="alert alert-info">
            <?= Yii::t('BbbModule.base', 'No conference sessions found.'); ?>
        </div>
    <?php } ?>
    <div class="row cards align-items-start">
        <?php foreach ($rows as $row): ?>
            <?= SessionCard::widget([
                'session' => $row['model'],
                'running' => $row['running'],
                'contentContainer' => $this->context->contentContainer,
                'highlightId' => $highlightId ?? 0,
            ]) ?>
        <?php endforeach; ?>
    </div>
    <div class="panel panel-default">
        <div class="panel-body">
            <?= Yii::t('BbbModule.base', 'You can create a new session by clicking the button above.'); ?>
        </div>
    </div>
</div>
<?php

$this->registerJs("
    if (top != self) top.location.href = location.href;    
", \yii\web\View::POS_HEAD);

$this->registerJs(<<<JS

    const highlighted = document.querySelector('.card-bbb-sessions.highlight');

    if (highlighted) {
        highlighted.scrollIntoView({
            behavior: 'smooth',  // sanftes Scrollen
            block: 'center'      // mittig im Container
        });
    }
JS
    ,
    \yii\web\View::POS_READY
);
?>