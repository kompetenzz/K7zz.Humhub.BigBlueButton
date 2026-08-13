<?php
/**
 * Widget view: Single session as a card element.
 *
 * @var k7zz\humhub\bbb\models\Session $session           The session model
 * @var bool   $running                                   Whether the session is currently running
 * @var int    $highlightId                               ID of the session to highlight
 * @var string $isRunningUrl                             URL to check if the session is running (for polling)   
 * @var \humhub\modules\content\components\ContentContainerActiveRecord|null $contentContainer  The content container (space/user) or null
 */
use humhub\libs\Html;
use humhub\modules\ui\icon\widgets\Icon;
use k7zz\humhub\bbb\assets\BBBAssets;

$bundle = BBBAssets::register(view: $this);
$routePrefix = '/bbb/session';
if ($this->context->contentContainer) {
    $routePrefix = $this->context->contentContainer->createUrl($routePrefix);
}
$highlightClass = $session->id === $highlightId ? 'highlight' : '';
$imageUrl = $session->outputImage ? $session->outputImage->getUrl() : $bundle->baseUrl . '/images/conference.png';
$detailsLink = $routePrefix . '/' . $session->name;
$chatEnabled = (bool) (Yii::$app->getModule('bbb')->settings->get('integrateBbbChat') ?? false)
    && (bool) $session->integrate_bbb_chat;

?>

<div id="sessioncard-<?= $session->id ?>"
    class="col-lg-3 col-md-4 col-sm-6 col-12 card-bbb-sessions <?= $highlightClass ?>"
    data-bbb-check-state="<?= Html::encode($isRunningUrl) ?>">
    <a href="<?= Html::encode($detailsLink) ?>" class="d-block text-decoration-none text-reset">
    <div class="card h-100">
        <?= $this->renderFile('@bbb/views/session/_sessionDetails.php', [
            'session' => $session,
            'running' => $running,
            'imageUrl' => $imageUrl,
            'linkUrl' => null,
            'top' => false,
        ]) ?>

        <?php if ($chatEnabled): ?>
            <div class="px-3 pb-2">
                <span class="label label-info" title="<?= Html::encode(Yii::t('BbbModule.base', 'Session chat')) ?>">
                    <?= Icon::get('comments') ?> <?= Html::encode(Yii::t('BbbModule.base', 'Session chat')) ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
    </a>
</div>