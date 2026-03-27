<?php
use humhub\libs\Html;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\topic\models\Topic;
use humhub\modules\topic\widgets\TopicLabel;
use humhub\modules\ui\icon\widgets\Icon;

/* @var $this \yii\web\View */
/* @var $model \k7zz\humhub\bbb\models\Session */
/* @var $running bool */
/* @var $imageUrl string */
/* @var $top bool If we are in list context or as a fullpage view */
/* @var $linkUrl string|null URL to wrap image and title with, or null */

$headingTag = $top ? 'h1' : 'h4';

?>


<?php if (!empty($linkUrl)): ?>
    <a href="<?= Html::encode($linkUrl) ?>">
    <?php endif; ?>
    <img class="card-img-top" alt="<?= Html::encode($model->title) ?>" src="<?= Html::encode($imageUrl) ?>" />
    <?php if (!empty($linkUrl)): ?>
    </a>
<?php endif; ?>

<div class="card-body">
    <?= Html::beginTag($headingTag, ['class' => 'card-title']) ?>
    <?php if (!empty($linkUrl)): ?>
        <a href="<?= Html::encode($linkUrl) ?>"
            class="text-body text-decoration-none"><?= Html::encode($model->title) ?></a>
    <?php else: ?>
        <?= Html::encode($model->title) ?>
    <?php endif; ?>
    <?php if ($model->is_space_default): ?>
        <span class="badge bg-secondary ms-1" title="<?= Yii::t('BbbModule.base', 'Space default session') ?>">
            <?= Yii::t('BbbModule.base', 'Default') ?>
        </span>
    <?php endif; ?>
    <span class="float-end">
        <?= $running
            ? '<span class="text-success" title="' . Yii::t('BbbModule.base', 'Running') . '">' . Icon::get('play') . '</span>'
            : '<span class="text-warning" title="' . Yii::t('BbbModule.base', 'Stopped') . '">' . Icon::get('pause') . '</span>' ?>
        <?php if ($model->isModerator()): ?>
            <span class="text-info"
                title="<?= Yii::t('BbbModule.base', 'You are moderator') ?>"><?= Icon::get('user-secret') ?></span>
        <?php endif; ?>
    </span>
    <?= Html::endTag($headingTag) ?>

    <?php if ($model->description): ?>
        <p class="card-text">
            <?= RichText::output($model->description) ?>
        </p>
    <?php endif; ?>

    <?php $topics = Topic::findByContent($model->content)->all(); ?>
    <?php if (!empty($topics)): ?>
        <div class="topic-label-list">
            <?php foreach ($topics as $topic): ?>
                <?= TopicLabel::forTopic($topic, $this->context->contentContainer) ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>