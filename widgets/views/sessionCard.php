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
use yii\helpers\Url;

$bundle = BBBAssets::register(view: $this);
$routePrefix = '/bbb/session';
if ($this->context->contentContainer) {
    $routePrefix = $this->context->contentContainer->createUrl($routePrefix);
}
$highlightClass = $session->id === $highlightId ? 'highlight' : '';
$imageUrl = $session->outputImage ? $session->outputImage->getUrl() : $bundle->baseUrl . '/images/conference.png';
$detailsLink = $routePrefix . '/' . $session->name;
$membersJoinLink = $routePrefix . '/join/' . $session->name;
$sessionLink = $routePrefix . '/' . $session->name;

?>

<div id="sessioncard-<?= $session->id ?>"
    class="col-lg-3 col-md-4 col-sm-6 col-12 card-bbb-sessions <?= $highlightClass ?>"
    data-bbb-check-state="<?= Html::encode($isRunningUrl) ?>">
    <div class="card">
        <?= $this->renderFile('@bbb/views/session/_sessionDetails.php', [
            'session' => $session,
            'running' => $running,
            'imageUrl' => $imageUrl,
            'linkUrl' => $detailsLink,
            'top' => false,
        ]) ?>
        <?php if ($session->canJoin()): ?>
            <div class="card-footer">
                <div class="bbb-waiting" style="display: <?= $running ? 'none' : '' ?>;">
                    <?php if ($session->canStart()): ?>
                        <?= Html::a(
                            Icon::get('video-camera') . ' ' . Yii::t('BbbModule.base', 'Start'),
                            '#',
                            [
                                'class' => 'btn btn-primary btn-sm bbb-launch-window',
                                'data-url' => $routePrefix . '/start/' . $session->name . '?embed=0',
                                'title' => Yii::t('BbbModule.base', 'Start session'),
                            ]
                        ) ?>
                    <?php elseif ($session->canJoin()): ?>
                        <?= Html::a(
                            Icon::get('clock') . ' ' . Yii::t('BbbModule.base', 'Enter waitingroom'),
                            '#',
                            [
                                'class' => 'btn btn-primary btn-sm bbb-launch-window',
                                'data-url' => $membersJoinLink,
                                'title' => Yii::t('BbbModule.base', 'Enter the waitingroom until the session starts'),
                            ]
                        ) ?>
                    <?php endif; ?>
                </div>
                <div class="bbb-running" style="display: <?= $running ? '' : 'none' ?>;">
                    <?php if ($session->canJoin()): ?>
                        <?= Html::a(
                            Icon::get('video-camera') . ' ' . Yii::t('BbbModule.base', 'Join'),
                            '#',
                            [
                                'class' => 'btn btn-primary btn-sm bbb-launch-window',
                                'data-url' => $membersJoinLink,
                                'title' => Yii::t('BbbModule.base', 'Join session'),
                            ]
                        ) ?>
                    <?php endif; ?>
                </div>

                <span id="bbb-members-url-<?= $session->id ?>" class="d-none"><?= Url::to([$sessionLink], true) ?></span>
                <?= Html::a(
                    Icon::get('lock') . ' ' . Yii::t('BbbModule.base', 'Members join link'),
                    '#',
                    [
                        'class' => 'btn btn-danger btn-sm',
                        'title' => Yii::t('BbbModule.base', 'Copy members access URL to clipboard'),
                        'data-action-click' => 'copyToClipboard',
                        'data-action-target' => '#bbb-members-url-' . $session->id
                    ]
                ) ?>

                <?php if ($session->canAdminister() && $session->public_join && $session->public_token): ?>
                    <span id="bbb-public-url-<?= $session->id ?>"
                        class="d-none"><?= Url::to(['/bbb/public/join', 'token' => $session->public_token], true) ?></span>
                    <?= Html::a(
                        Icon::get('link') . ' ' . Yii::t('BbbModule.base', 'Public Join link'),
                        '#',
                        [
                            'class' => 'btn btn-success btn-sm',
                            'title' => Yii::t('BbbModule.base', 'Copy public access URL to clipboard'),
                            'data-action-click' => 'copyToClipboard',
                            'data-action-target' => '#bbb-public-url-' . $session->id
                        ]
                    ) ?>
                <?php endif; ?>

                <?php if ($session->canAdminister()): ?>
                    <span class="float-end">
                        <?= Html::a(
                            Icon::get('pencil'),
                            $routePrefix . '/edit/' . $session->name,
                            [
                                'class' => 'btn btn-info btn-sm',
                                'title' => Yii::t('BbbModule.base', 'Edit session')
                            ]
                        ) ?>
                        <?= Html::a(
                            Icon::get('trash'),
                            $routePrefix . '/delete/' . $session->name,
                            [
                                'class' => 'btn btn-danger btn-sm',
                                'data-confirm' => Yii::t('BbbModule.base', 'Are you sure you want to delete this session?'),
                                'data-method' => 'post',
                                'title' => Yii::t('BbbModule.base', 'Delete session'),
                                'aria-label' => Yii::t('BbbModule.base', 'Delete session'),
                            ]
                        ) ?>
                    </span>
                <?php endif; ?>
            </div>
            <?= $this->renderFile('@bbb/views/session/_recordingsBox.php', [
                'model' => $session,
                'contentContainer' => $this->context->contentContainer,
            ]) ?>
        <?php endif; ?>
    </div>
</div>