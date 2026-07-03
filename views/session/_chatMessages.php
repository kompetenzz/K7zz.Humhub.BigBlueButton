<?php

use humhub\libs\Html;
use k7zz\humhub\bbb\models\SessionMeetingChat;

/* @var $this \yii\web\View */
/* @var $messages \k7zz\humhub\bbb\models\SessionMeetingChat[] */

if (empty($messages)): ?>
    <p class="text-muted small mb-0 bbb-chat-empty">
        <?= Yii::t('BbbModule.base', 'No messages yet. Write something before or during the meeting.') ?>
    </p>
<?php
    return;
endif;

$avatarColors = ['#6c757d','#0d6efd','#198754','#fd7e14','#6f42c1','#0dcaf0','#d63384'];

$lastMeetingId = false;

foreach ($messages as $msg):
    $pending = ($msg->sent_at === null && $msg->source === SessionMeetingChat::SOURCE_HUMHUB);
    $fromBbb = ($msg->source === SessionMeetingChat::SOURCE_BBB);
    $name    = $msg->sender_name ?: Yii::t('BbbModule.base', 'Unknown');
    $time    = Yii::$app->formatter->asTime($msg->created_at, 'short');

    // Resolve user model for avatar (HumHub messages have queuedByUser, BBB messages may have user)
    $user = $msg->queuedByUser ?? $msg->user;

    // Avatar: real profile image or colored initial bubble
    $initial   = mb_strtoupper(mb_substr($name, 0, 1)) ?: '?';
    $color     = $avatarColors[abs(crc32($name)) % count($avatarColors)];
    $hasImage  = $user && $user->getProfileImage()->hasImage();
    $avatarUrl = $hasImage ? $user->getProfileImage()->getUrl() : null;

    // Meeting boundary separator
    if ($msg->session_meeting_id !== null && $msg->session_meeting_id !== $lastMeetingId) {
        if ($lastMeetingId !== false): ?>
            <div class="bbb-chat-divider"><span><?= Yii::t('BbbModule.base', 'New meeting') ?></span></div>
        <?php else: ?>
            <div class="bbb-chat-divider"><span><?= Yii::t('BbbModule.base', 'Meeting started') ?></span></div>
        <?php endif;
        $lastMeetingId = $msg->session_meeting_id;
    } elseif ($msg->session_meeting_id === null && $lastMeetingId !== false) {
        $lastMeetingId = null; ?>
        <div class="bbb-chat-divider"><span><?= Yii::t('BbbModule.base', 'After meeting') ?></span></div>
    <?php } ?>

    <div class="bbb-chat-msg <?= $pending ? 'bbb-chat-pending' : '' ?>">
        <div class="bbb-chat-avatar-wrap">
            <?php if ($avatarUrl): ?>
                <img src="<?= Html::encode($avatarUrl) ?>"
                     class="bbb-chat-avatar"
                     alt="<?= Html::encode($name) ?>">
            <?php else: ?>
                <div class="bbb-chat-avatar bbb-chat-avatar-initial"
                     style="background:<?= $color ?>;"
                     title="<?= Html::encode($name) ?>"><?= Html::encode($initial) ?></div>
            <?php endif; ?>
        </div>
        <div class="bbb-chat-body">
            <div class="bbb-chat-meta">
                <strong><?= Html::encode($name) ?></strong>
                <span class="text-muted small ms-1"><?= Html::encode($time) ?></span>
                <?php if ($fromBbb): ?>
                    <span class="text-muted small ms-1" title="<?= Yii::t('BbbModule.base', 'Received from BigBlueButton') ?>">· BBB</span>
                <?php endif; ?>
                <?php if ($pending): ?>
                    <span class="badge bg-warning text-dark ms-1"
                          title="<?= Yii::t('BbbModule.base', 'Will be delivered when the meeting starts') ?>">
                        <?= Yii::t('BbbModule.base', 'Queued') ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="bbb-chat-text"><?= nl2br(Html::encode($msg->message)) ?></div>
        </div>
    </div>
<?php endforeach; ?>
