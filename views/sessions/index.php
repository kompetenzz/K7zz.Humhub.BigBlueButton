<?php
use humhub\libs\Html;
use k7zz\humhub\bbb\widgets\SessionCard;
use k7zz\humhub\bbb\permissions\Admin;
use humhub\modules\ui\icon\widgets\Icon;
$containerId = $this->context->contentContainer
    ? $this->context->contentContainer->id
    : null;
$canCreate = $scope === 'container'
    ? $this->context->container->can(Admin::class)
    : Yii::$app->user->can(Admin::class);

?>
<div class="container-fluid container-cards container-bbb-sessions">
    <div class="panel panel-default">
        <div class="panel-heading">
            <?php if ($canCreate) { ?>
                <?= Html::a(
                    Icon::get('plus') . ' ' . Yii::t('BbbModule.base', 'Create session'),
                    ['/bbb/session/create', 'containerId' => $containerId],
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
    <div class="row cards">
        <?php foreach ($rows as $row): ?>
            <?= SessionCard::widget([
                'session' => $row['model'],
                'running' => $row['running'],
                'contentContainer' => $this->context->contentContainer,
                'scope' => $scope,
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
/* JS, um den User-Picker live ein- und auszublenden */
$this->registerJs("
    if (top != self) top.location.href = location.href;    
", \yii\web\View::POS_HEAD);
?>