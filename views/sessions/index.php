<?php
/**
 * View: List and manage BBB sessions for a container or globally.
 *
 * Variables:
 * @var array  $rows           List of session data (model, running) — used in 'global' viewMode
 * @var int    $highlightId    ID of the session to highlight (optional)
 * @var string $viewMode       'global' or 'all'
 * @var bool   $isAdmin        Whether the current user has admin permission
 * @var array  $globalRows     Rows for global sessions (viewMode=all)
 * @var array  $spaceGroups    Space-grouped rows  ['container'=>Space, 'rows'=>[…]]
 * @var array  $userGroups     User-grouped rows   ['container'=>User,  'rows'=>[…]]
 * @var \humhub\modules\content\components\ContentContainerActiveRecord|null $contentContainer
 */

use humhub\libs\Html;
use k7zz\humhub\bbb\widgets\SessionCard;
use k7zz\humhub\bbb\permissions\Admin;
use humhub\modules\ui\icon\widgets\Icon;
use yii\helpers\Url;

$this->setPageTitle(Yii::t('BbbModule.base', 'Sessions'));

$createUrl = $this->context->contentContainer
    ? $this->context->contentContainer->createUrl('/bbb/session/create')
    : Url::to('/bbb/session/create');

$canCreate = $this->context->contentContainer
    ? $this->context->contentContainer->can(Admin::class)
    : Yii::$app->user->can(Admin::class);

// Toggle URLs — only relevant when no container (global admin view)
$urlGlobal = Url::to(['/bbb/sessions/index', 'view' => 'global']);
$urlAll = Url::to(['/bbb/sessions/index', 'view' => 'all']);

// Render a group of session rows.
// In the 'all' view the context container is null, so we use each session's own container
// so that URLs are built with the correct space/user prefix.
$contextContainer = $this->context->contentContainer;
$renderRows = function (array $rows) use ($highlightId, $contextContainer): string {
    if (empty($rows)) {
        return '<p class="text-muted">' . Yii::t('BbbModule.base', 'No sessions.') . '</p>';
    }
    $html = '<div class="row g-3 align-items-start">';
    foreach ($rows as $row) {
        $container = $contextContainer ?? ($row['model']->content->container ?? null);
        $html .= SessionCard::widget([
            'session'          => $row['model'],
            'running'          => $row['running'],
            'contentContainer' => $container,
            'highlightId'      => $highlightId ?? 0,
        ]);
    }
    $html .= '</div>';
    return $html;
};
?>
<div class="container-fluid container-bbb-sessions">
    <div class="card mb-3 bg-white">
        <div class="card-header d-flex align-items-center flex-wrap gap-2">

            <?php if ($canCreate): ?>
                <?= Html::a(
                    Icon::get('plus') . ' ' . Yii::t('BbbModule.base', 'Create session'),
                    $createUrl,
                    ['class' => 'btn btn-secondary ms-auto', 'style' => 'margin: 10px']
                ); ?>
            <?php endif; ?>

            <h1 class="mb-0 me-auto"><?= Yii::t('BbbModule.base', 'Conference sessions') ?></h1>

            <?php if ($isAdmin && !$this->context->contentContainer): ?>
                <div class="btn-group ms-3" role="group" aria-label="<?= Yii::t('BbbModule.base', 'Session view') ?>">
                    <?= Html::a(
                        Yii::t('BbbModule.base', 'Global sessions'),
                        $urlGlobal,
                        ['class' => 'btn btn-sm ' . ($viewMode === 'global' ? 'btn-primary' : 'btn-outline-primary')]
                    ) ?>
                    <?= Html::a(
                        Yii::t('BbbModule.base', 'All sessions'),
                        $urlAll,
                        ['class' => 'btn btn-sm ' . ($viewMode === 'all' ? 'btn-primary' : 'btn-outline-primary')]
                    ) ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <?php if ($viewMode === 'all'): ?>

        <?php
        $totalCount = count($globalRows) + array_sum(array_map(fn($g) => count($g['rows']), $spaceGroups))
            + array_sum(array_map(fn($g) => count($g['rows']), $userGroups));
        ?>

        <?php if ($totalCount === 0): ?>
            <div class="alert alert-info">
                <?= Yii::t('BbbModule.base', 'No conference sessions found.') ?>
            </div>
        <?php endif; ?>

        <!-- 1. Globale Sessions -->
        <div class="card mb-4 bbb-section">
            <div class="card-header bg-light text-dark">
                <h2 class="mb-0"><?= Icon::get('globe') ?>     <?= Yii::t('BbbModule.base', 'Global sessions') ?></h2>
            </div>
            <div class="card-body bg-white">
                <?= $renderRows($globalRows) ?>
            </div>
        </div>

        <!-- 2. Space-Sessions -->
        <?php if (!empty($spaceGroups)): ?>
            <?php foreach ($spaceGroups as $group): ?>
                <div class="card mb-4 bbb-section">
                    <div class="card-header bg-light text-dark">
                        <?php $img = $group['container']->getProfileImage(); ?>
                        <h2 class="mb-0">
                            <?php if ($img->hasImage()): ?>
                                <img src="<?= Html::encode($img->getUrl()) ?>" class="bbb-avatar" alt="">
                            <?php else: ?>
                                <?= Icon::get('users') ?>
                            <?php endif; ?>
                            <?= Html::encode($group['container']->displayName ?? $group['container']->name ?? '–') ?>
                        </h2>
                    </div>
                    <div class="card-body bg-white">
                        <?= $renderRows($group['rows']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- 3. User-Sessions -->
        <?php if (!empty($userGroups)): ?>
            <div class="card mb-4 bbb-section">
                <?php foreach ($userGroups as $group): ?>
                    <div class="card-header bg-light text-dark">
                        <?php $img = $group['container']->getProfileImage(); ?>
                        <h2 class="mb-0">
                            <?php if ($img->hasImage()): ?>
                                <img src="<?= Html::encode($img->getUrl()) ?>" class="bbb-avatar" alt="">
                            <?php else: ?>
                                <?= Icon::get('user') ?>
                            <?php endif; ?>
                            <?= Html::encode($group['container']->displayName ?? $group['container']->username ?? '–') ?>
                        </h2>
                    </div>
                    <div class="card-body bg-white">
                        <?= $renderRows($group['rows']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php else: ?>

        <?php if (empty($rows)): ?>
            <div class="alert alert-info">
                <?= Yii::t('BbbModule.base', 'No conference sessions found.') ?>
            </div>
        <?php endif; ?>

        <div class="row g-3 align-items-start">
            <?php foreach ($rows as $row): ?>
                <?= SessionCard::widget([
                    'session' => $row['model'],
                    'running' => $row['running'],
                    'contentContainer' => $this->context->contentContainer,
                    'highlightId' => $highlightId ?? 0,
                ]) ?>
            <?php endforeach; ?>
        </div>

        <div class="card mt-3 bg-white">
            <div class="card-body">
                <?php if (empty($rows)): ?>
                    <?= Yii::t('BbbModule.base', 'You can create a new session by clicking the button above.') ?>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>
</div>
<?php

$this->registerCss(<<<CSS
.card.bbb-section {
    border-left: 4px solid var(--bs-dark);
}
.bbb-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}
CSS);

$this->registerJs("
    if (top != self) top.location.href = location.href;
", \yii\web\View::POS_HEAD);

$this->registerJs(<<<JS

    const highlighted = document.querySelector('.card-bbb-sessions.highlight');

    if (highlighted) {
        highlighted.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }
JS
    ,
    \yii\web\View::POS_READY
);
?>