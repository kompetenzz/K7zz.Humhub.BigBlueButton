<?php
use humhub\libs\Html;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\topic\models\Topic;
use humhub\modules\topic\widgets\TopicLabel;
use humhub\modules\ui\icon\widgets\Icon;

/* @var $this \yii\web\View */
/* @var $session \k7zz\humhub\bbb\models\Session */
/* @var $running bool */
/* @var $imageUrl string */
/* @var $top bool If we are in list context or as a fullpage view */
/* @var $linkUrl string|null URL to wrap image and title with, or null */

$headingTag = $top ? 'h1' : 'h4';

?>


<?php if (!empty($linkUrl)): ?>
    <a href="<?= Html::encode($linkUrl) ?>">
    <?php endif; ?>
    <img class="card-img-top" alt="<?= Html::encode($session->title) ?>" src="<?= Html::encode($imageUrl) ?>" />
    <?php if (!empty($linkUrl)): ?>
    </a>
<?php endif; ?>

<div class="card-body">
    <?= Html::beginTag($headingTag, ['class' => 'card-title']) ?>
    <?php if (!empty($linkUrl)): ?>
        <a href="<?= Html::encode($linkUrl) ?>"
            class="text-body text-decoration-none"><?= Html::encode($session->title) ?></a>
    <?php else: ?>
        <?= Html::encode($session->title) ?>
    <?php endif; ?>
    <?php if ($session->is_space_default): ?>
        <span class="badge bg-secondary ms-1" title="<?= Yii::t('BbbModule.base', 'Space default session') ?>">
            <?= Yii::t('BbbModule.base', 'Default') ?>
        </span>
    <?php endif; ?>
    <span class="float-end">
        <span class="text-success bbb-running" style="display: <?= $running ? '' : 'none' ?>;"
            title="<?= Yii::t('BbbModule.base', 'Running') ?>">
            <?= Icon::get('play') ?>
        </span>
        <span class="text-warning bbb-waiting" style="display: <?= $running ? 'none' : '' ?>;"
            title="<?= Yii::t('BbbModule.base', 'Stopped') ?>">
            <?= Icon::get('pause') ?>
        </span>
        <?php if ($session->isModerator()): ?>
            <span class="text-info"
                title="<?= Yii::t('BbbModule.base', 'You are moderator') ?>"><?= Icon::get('key') ?></span>
        <?php endif; ?>
    </span>
    <?= Html::endTag($headingTag) ?>

    <?php if ($session->description): ?>
        <p class="card-text">
            <?= RichText::output($session->description) ?>
        </p>
    <?php endif; ?>

    <?php $topics = Topic::findByContent($session->content)->all(); ?>
    <?php if (!empty($topics)): ?>
        <div class="topic-label-list">
            <?php foreach ($topics as $topic): ?>
                <?= TopicLabel::forTopic($topic, $this->context->contentContainer) ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>