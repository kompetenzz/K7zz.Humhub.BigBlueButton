<?php

use humhub\libs\Html;

/* @var $this \yii\web\View */
/* @var $messages \k7zz\humhub\bbb\models\SessionMeetingChat[] */

if (empty($messages)): ?>
    <p class="text-muted small mb-0 bbb-chat-empty">
        <?= Yii::t('BbbModule.base', 'No messages yet. Be the first to write something.') ?>
    </p>
<?php
    return;
endif;

foreach ($messages as $msg):
    $pending = ($msg->sent_at === null);
    $name    = Html::encode($msg->sender_name ?: Yii::t('BbbModule.base', 'Unknown'));
    $time    = Yii::$app->formatter->asDatetime($msg->created_at, 'short');
?>
<div class="bbb-chat-msg <?= $pending ? 'bbb-chat-pending' : '' ?>">
    <div class="bbb-chat-meta">
        <strong><?= $name ?></strong>
        <span class="text-muted small ms-1"><?= Html::encode($time) ?></span>
        <?php if ($pending): ?>
            <span class="badge bg-warning text-dark ms-1" title="<?= Yii::t('BbbModule.base', 'Will be delivered when the meeting starts') ?>">
                <?= Yii::t('BbbModule.base', 'Queued') ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="bbb-chat-text"><?= nl2br(Html::encode($msg->message)) ?></div>
</div>
<?php endforeach; ?>
