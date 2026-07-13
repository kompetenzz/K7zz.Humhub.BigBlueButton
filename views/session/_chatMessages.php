<?php

use humhub\libs\Html;
use k7zz\humhub\bbb\models\SessionChatReaction;
use k7zz\humhub\bbb\models\SessionMeeting;
use k7zz\humhub\bbb\models\SessionMeetingChat;

/* @var $this \yii\web\View */
/* @var $messages \k7zz\humhub\bbb\models\SessionMeetingChat[] */
/* @var $session \k7zz\humhub\bbb\models\Session|null */
$session = $session ?? null;

if (empty($messages)): ?>
    <p class="text-muted small mb-0 bbb-chat-empty">
        <?= Yii::t('BbbModule.base', 'No messages yet. Write something before or during the meeting.') ?>
    </p>
<?php
    return;
endif;

$meetingIds = array_filter(array_unique(array_column($messages, 'session_meeting_id')));
$meetings   = $meetingIds
    ? SessionMeeting::find()->where(['id' => array_values($meetingIds)])->indexBy('id')->all()
    : [];

// name→user map from HumHub messages — fallback avatar source for BBB messages with null user_id
$nameToUser = [];
foreach ($messages as $_m) {
    if ($_m->user_id !== null && $_m->user !== null && !isset($nameToUser[$_m->sender_name])) {
        $nameToUser[$_m->sender_name] = $_m->user;
    }
}

$fmt           = Yii::$app->formatter;
$avatarColors  = ['#6c757d', '#0d6efd', '#198754', '#fd7e14', '#6f42c1', '#0dcaf0', '#d63384'];
$currentUserId = Yii::$app->user->isGuest ? null : Yii::$app->user->id;

$chatIds = array_column(
    array_filter($messages, fn($m) => $m->source !== SessionMeetingChat::SOURCE_SYSTEM),
    'id'
);
$allReactions = SessionChatReaction::findGroupedForChats($chatIds, $currentUserId);
$canReact     = $currentUserId !== null;
$canModerate  = $session !== null && ($session->isModerator() || $session->canAdminister());

$prevMeetingId = PHP_INT_MIN;
$prevDate      = null;

foreach ($messages as $msg):
    $curId   = $msg->session_meeting_id;
    $curDate = date('Y-m-d', $msg->created_at);

    $isSystemMsg   = ($msg->source === SessionMeetingChat::SOURCE_SYSTEM);
    $isSystemStart = $isSystemMsg && $msg->message === 'meeting-started';
    $isSystemEnd   = $isSystemMsg && $msg->message === 'meeting-ended';

    // --- Day divider (always, independent of meeting state) ---
    if ($curDate !== $prevDate): ?>
        <div class="bbb-chat-divider bbb-chat-divider--date">
            <span><?= Html::encode($fmt->asDate($msg->created_at, 'full')) ?></span>
        </div>
    <?php
        $prevDate = $curDate;
    endif;

    // --- Meeting boundary auto-dividers (suppressed when system message handles the boundary) ---
    if ($curId !== $prevMeetingId):
        if ($prevMeetingId !== PHP_INT_MIN && $prevMeetingId !== null && !$isSystemEnd):
            $prev    = $meetings[$prevMeetingId] ?? null;
            $endTime = $prev?->ended_at ? $fmt->asTime($prev->ended_at, 'short') : ''; ?>
            <div class="bbb-chat-divider bbb-chat-divider--end">
                <span><?= Yii::t('BbbModule.base', 'Meeting End') ?><?= $endTime ? ' · ' . Html::encode($endTime) : '' ?></span>
            </div>
        <?php endif;

        if ($curId !== null && !$isSystemStart):
            $cur       = $meetings[$curId] ?? null;
            $startTime = $cur?->started_at ? $fmt->asTime($cur->started_at, 'short') : ''; ?>
            <div class="bbb-chat-divider bbb-chat-divider--start">
                <span><?= Yii::t('BbbModule.base', 'Meeting Start') ?><?= $startTime ? ' · ' . Html::encode($startTime) : '' ?></span>
            </div>
        <?php endif;

        $prevMeetingId = $curId;
    endif;

    // --- System messages render as dividers, not bubbles ---
    if ($isSystemStart):
        $cur       = $meetings[$curId] ?? null;
        $startTime = $cur?->started_at
            ? $fmt->asTime($cur->started_at, 'short')
            : $fmt->asTime($msg->created_at, 'short'); ?>
        <div class="bbb-chat-divider bbb-chat-divider--start">
            <span><?= Yii::t('BbbModule.base', 'Meeting Start') ?> · <?= Html::encode($startTime) ?></span>
        </div>
    <?php elseif ($isSystemEnd):
        $endTime = $fmt->asTime($msg->created_at, 'short'); ?>
        <div class="bbb-chat-divider bbb-chat-divider--end">
            <span><?= Yii::t('BbbModule.base', 'Meeting End') ?> · <?= Html::encode($endTime) ?></span>
        </div>
    <?php elseif ($isSystemMsg && $msg->message === 'recording-started'): ?>
        <div class="bbb-chat-divider bbb-chat-divider--recording">
            <span>&#9679; <?= Yii::t('BbbModule.base', 'Recording started') ?> · <?= Html::encode($fmt->asTime($msg->created_at, 'short')) ?></span>
        </div>
    <?php elseif ($isSystemMsg && $msg->message === 'recording-stopped'): ?>
        <div class="bbb-chat-divider bbb-chat-divider--recording-stopped">
            <span>&#9632; <?= Yii::t('BbbModule.base', 'Recording stopped') ?> · <?= Html::encode($fmt->asTime($msg->created_at, 'short')) ?></span>
        </div>
    <?php endif;

    if ($isSystemMsg): continue; endif;

    $fromBbb = ($msg->source === SessionMeetingChat::SOURCE_BBB);
    $isOwn   = $currentUserId !== null && (
        ($msg->user_id !== null && $msg->user_id === $currentUserId)
        || ($msg->user_id === null && $msg->sender_name === (Yii::$app->user->identity->displayName ?? ''))
    );
    $name    = $msg->sender_name ?: Yii::t('BbbModule.base', 'Unknown');
    $time    = $fmt->asTime($msg->created_at, 'short');
    $user    = $msg->user ?? $nameToUser[$name] ?? null;

    $initial   = mb_strtoupper(mb_substr($name, 0, 1)) ?: '?';
    $color     = $avatarColors[abs(crc32($name)) % count($avatarColors)];
    $avatarUrl = $user ? $user->getProfileImage()->getUrl() : null;

    $canEditMsg   = $currentUserId !== null
        && $msg->user_id === $currentUserId
        && $msg->source === SessionMeetingChat::SOURCE_HUMHUB;
    $canDeleteMsg = ($currentUserId !== null && $msg->user_id === $currentUserId) || $canModerate;
    ?>

    <div class="bbb-chat-msg <?= $isOwn ? 'bbb-chat-msg--own' : '' ?>"
         data-chat-id="<?= $msg->id ?>"<?= $canEditMsg ? ' data-raw="' . Html::encode($msg->message) . '"' : '' ?>>
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
                <?php if (!$isOwn): ?>
                    <strong class="bbb-chat-sender"><?= Html::encode($name) ?></strong>
                <?php endif; ?>
                <span class="text-muted small"><?= Html::encode($time) ?></span>
                <?php if ($msg->edited_at): ?>
                    <span class="text-muted small fst-italic"
                          title="<?= Html::encode($fmt->asDatetime($msg->edited_at, 'short')) ?>">· <?= Yii::t('BbbModule.base', 'edited') ?></span>
                <?php endif; ?>
                <?php if ($fromBbb): ?>
                    <span class="text-muted small" title="<?= Yii::t('BbbModule.base', 'Received from BigBlueButton') ?>">· BBB</span>
                <?php endif; ?>
            </div>
            <div class="bbb-chat-bubble <?= $isOwn ? 'bbb-chat-bubble--own' : 'bbb-chat-bubble--other' ?>">
                <?= $msg->getFormattedMessage() ?>
            </div>
            <?php $msgReactions = $allReactions[$msg->id] ?? []; ?>
            <?php if ($msgReactions || $canReact): ?>
                <div class="bbb-chat-reactions" data-chat-id="<?= $msg->id ?>">
                    <?php foreach ($msgReactions as $emoji => $info): ?>
                        <span class="bbb-chat-reaction<?= $info['own'] ? ' bbb-chat-reaction--own' : '' ?>"
                              data-emoji="<?= Html::encode($emoji) ?>"
                              title="<?= Html::encode(implode(', ', $info['names'])) ?>"
                        ><?= $emoji ?> <?= $info['count'] ?></span>
                    <?php endforeach; ?>
                    <?php if ($canReact): ?>
                        <span class="bbb-chat-reaction-add" title="<?= Yii::t('BbbModule.base', 'React') ?>">☺&#65038;+</span>
                        <span class="bbb-chat-reaction-picker" style="display:none;">
                            <?php foreach (SessionChatReaction::ALLOWED_EMOJIS as $emoji): ?>
                                <span data-emoji="<?= Html::encode($emoji) ?>"><?= $emoji ?></span>
                            <?php endforeach; ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($canEditMsg): ?>
                        <span class="bbb-chat-action bbb-chat-edit" title="<?= Yii::t('BbbModule.base', 'Edit message') ?>"><i class="fa fa-pencil"></i></span>
                    <?php endif; ?>
                    <?php if ($canDeleteMsg): ?>
                        <span class="bbb-chat-action bbb-chat-delete" title="<?= Yii::t('BbbModule.base', 'Delete message') ?>"><i class="fa fa-trash"></i></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
