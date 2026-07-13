<?php

use humhub\libs\Html;
use humhub\modules\ui\icon\widgets\Icon;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $session \k7zz\humhub\bbb\models\Session */
/* @var $running bool */
/* @var $messages \k7zz\humhub\bbb\models\SessionMeetingChat[] */

$globalEnabled = (bool) (Yii::$app->getModule('bbb')->settings->get('integrateBbbChat') ?? false);
if (!$globalEnabled || !$session->integrate_bbb_chat) {
    return;
}

$routeBase = fn($route, $params = []) => $this->context->contentContainer
    ? $this->context->contentContainer->createUrl($route, $params)
    : Url::to(array_merge([$route], $params));

$queueUrl    = $routeBase('/bbb/session/send-chat', ['id' => $session->id]);
$messagesUrl = $routeBase('/bbb/session/chat-messages', ['id' => $session->id]);
$reactUrl    = $routeBase('/bbb/session/chat-react', ['id' => $session->id]);
$editUrl     = $routeBase('/bbb/session/chat-edit', ['id' => $session->id]);
$deleteUrl   = $routeBase('/bbb/session/chat-delete', ['id' => $session->id]);

?>
<div class="bbb-chat-box" id="bbb-chat-box-<?= $session->id ?>">
    <div class="bbb-panel-heading">
        <?= Icon::get('comment') ?> <?= Yii::t('BbbModule.base', 'Session chat') ?>
        <?php if ($running): ?>
            <span class="badge bg-success ms-1"><?= Yii::t('BbbModule.base', 'Live') ?></span>
        <?php endif; ?>
    </div>

    <div class="bbb-chat-messages mb-2" id="bbb-chat-messages-<?= $session->id ?>"
         data-react-url="<?= Html::encode($reactUrl) ?>"
         data-edit-url="<?= Html::encode($editUrl) ?>"
         data-delete-url="<?= Html::encode($deleteUrl) ?>">
        <?= $this->renderFile('@bbb/views/session/_chatMessages.php', [
            'messages' => $messages,
            'session'  => $session,
        ]) ?>
    </div>

    <div class="bbb-chat-form">
        <div class="input-group">
            <textarea id="bbb-chat-input-<?= $session->id ?>"
                class="form-control form-control-sm"
                rows="2"
                placeholder="<?= Html::encode(Yii::t('BbbModule.base', 'Write a message…')) ?>"
            ></textarea>
            <button class="btn btn-outline-primary btn-sm bbb-chat-send"
                data-session-id="<?= $session->id ?>"
                data-url="<?= Html::encode($queueUrl) ?>"
                data-messages-url="<?= Html::encode($messagesUrl) ?>"
                title="<?= Yii::t('BbbModule.base', 'Send message') ?>">
                <?= Icon::get('paper-plane') ?>
            </button>
        </div>
        <div class="bbb-chat-feedback mt-1" id="bbb-chat-feedback-<?= $session->id ?>" style="display:none;"></div>
    </div>
</div>

<?php
$id           = $session->id;
$running      = (int) $running;
$errEmpty     = Html::encode(Yii::t('BbbModule.base', 'Please enter a message.'));
$errSend      = Html::encode(Yii::t('BbbModule.base', 'Could not send message. Please try again.'));
$successLabel = Html::encode(Yii::t('BbbModule.base', 'Message sent.'));
$confirmDel   = Html::encode(Yii::t('BbbModule.base', 'Delete this message?'));
$saveLabel    = Html::encode(Yii::t('BbbModule.base', 'Save'));
$cancelLabel  = Html::encode(Yii::t('BbbModule.base', 'Cancel'));

$this->registerJs(<<<JS
(function () {
    var \$btn   = \$('.bbb-chat-send[data-session-id="{$id}"]');
    var \$input = \$('#bbb-chat-input-{$id}');
    var \$msgs  = \$('#bbb-chat-messages-{$id}');
    var \$fb    = \$('#bbb-chat-feedback-{$id}');
    var running = {$running};
    var pollTimer = null;

    function sendMessage() {
        var msg = \$input.val().trim();
        if (!msg) {
            showFeedback('{$errEmpty}', 'text-danger');
            return;
        }
        \$btn.prop('disabled', true);
        \$.ajax({
            url:    \$btn.data('url'),
            method: 'POST',
            data:   { message: msg, _csrf: yii.getCsrfToken() },
        }).done(function (res) {
            if (res.status === 200) {
                \$input.val('');
                showFeedback('{$successLabel}', 'text-success');
                refreshMessages();
            } else {
                showFeedback(res.error || '{$errSend}', 'text-danger');
            }
        }).fail(function () {
            showFeedback('{$errSend}', 'text-danger');
        }).always(function () {
            \$btn.prop('disabled', false);
        });
    }

    function refreshMessages(keepScroll) {
        var pos = \$msgs.scrollTop();
        \$.get(\$btn.data('messages-url')).done(function(html) {
            \$msgs.html(html);
            \$msgs.scrollTop(keepScroll ? pos : 999999999);
        });
    }

    function sendReaction(chatId, emoji) {
        \$.ajax({
            url:    \$msgs.data('react-url'),
            method: 'POST',
            data:   { chatId: chatId, emoji: emoji, _csrf: yii.getCsrfToken() },
        }).done(function () {
            refreshMessages(true);
        });
    }

    // Delegated: content is replaced on every refresh
    \$msgs.on('click', '.bbb-chat-reaction', function () {
        sendReaction(\$(this).closest('.bbb-chat-reactions').data('chat-id'), \$(this).data('emoji'));
    });
    \$msgs.on('click', '.bbb-chat-reaction-add', function (e) {
        e.stopPropagation();
        var \$picker = \$(this).siblings('.bbb-chat-reaction-picker');
        \$msgs.find('.bbb-chat-reaction-picker').not(\$picker).hide();
        \$picker.toggle();
    });
    \$msgs.on('click', '.bbb-chat-reaction-picker span', function () {
        sendReaction(\$(this).closest('.bbb-chat-reactions').data('chat-id'), \$(this).data('emoji'));
    });
    \$(document).on('click', function () {
        \$msgs.find('.bbb-chat-reaction-picker').hide();
    });

    \$msgs.on('click', '.bbb-chat-delete', function () {
        if (!confirm('{$confirmDel}')) return;
        var chatId = \$(this).closest('.bbb-chat-msg').data('chat-id');
        \$.ajax({
            url:    \$msgs.data('delete-url'),
            method: 'POST',
            data:   { chatId: chatId, _csrf: yii.getCsrfToken() },
        }).done(function () {
            refreshMessages(true);
        });
    });

    \$msgs.on('click', '.bbb-chat-edit', function () {
        var \$m = \$(this).closest('.bbb-chat-msg');
        if (\$m.find('.bbb-chat-edit-form').length) return;

        var \$bubble = \$m.find('.bbb-chat-bubble');
        var \$form = \$(
            '<div class="bbb-chat-edit-form">' +
                '<textarea class="form-control form-control-sm" rows="2"></textarea>' +
                '<div class="bbb-chat-edit-actions mt-1">' +
                    '<button type="button" class="btn btn-primary btn-sm bbb-chat-edit-save">{$saveLabel}</button> ' +
                    '<button type="button" class="btn btn-secondary btn-sm bbb-chat-edit-cancel">{$cancelLabel}</button>' +
                '</div>' +
            '</div>'
        );
        \$form.find('textarea').val(\$m.attr('data-raw'));
        \$bubble.hide().after(\$form);
        \$form.find('textarea').focus();
    });

    \$msgs.on('click', '.bbb-chat-edit-cancel', function () {
        var \$m = \$(this).closest('.bbb-chat-msg');
        \$m.find('.bbb-chat-edit-form').remove();
        \$m.find('.bbb-chat-bubble').show();
    });

    function saveEdit(\$m) {
        var text = \$m.find('.bbb-chat-edit-form textarea').val().trim();
        if (!text) return;
        \$.ajax({
            url:    \$msgs.data('edit-url'),
            method: 'POST',
            data:   { chatId: \$m.data('chat-id'), message: text, _csrf: yii.getCsrfToken() },
        }).done(function () {
            refreshMessages(true);
        });
    }

    \$msgs.on('click', '.bbb-chat-edit-save', function () {
        saveEdit(\$(this).closest('.bbb-chat-msg'));
    });
    \$msgs.on('keydown', '.bbb-chat-edit-form textarea', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            saveEdit(\$(this).closest('.bbb-chat-msg'));
        } else if (e.key === 'Escape') {
            \$(this).closest('.bbb-chat-msg').find('.bbb-chat-edit-cancel').trigger('click');
        }
    });

    function showFeedback(msg, cls) {
        \$fb.removeClass('text-danger text-success').addClass(cls).text(msg).show();
        setTimeout(function() { \$fb.fadeOut(); }, 3000);
    }

    var scrolled = false;
    function scrollToBottom() {
        if (scrolled) return;
        scrolled = true;
        \$msgs.scrollTop(999999999);
    }

    \$btn.on('click', sendMessage);
    \$input.on('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Scroll after recordings box settles; fallback if recordings box not rendered
    \$(document).one('bbb:layout:{$id}', scrollToBottom);
    setTimeout(scrollToBottom, 1500);

    function autoRefresh() {
        // Don't wipe an open reaction picker or edit form
        if (\$msgs.find('.bbb-chat-reaction-picker:visible, .bbb-chat-edit-form').length) return;
        // Keep position while the user reads older messages; follow new ones at the bottom
        var nearBottom = \$msgs[0].scrollHeight - \$msgs.scrollTop() - \$msgs[0].clientHeight < 40;
        refreshMessages(!nearBottom);
    }

    function startPolling() {
        if (pollTimer === null) {
            pollTimer = setInterval(autoRefresh, 5000);
        }
    }

    function stopPolling() {
        clearInterval(pollTimer);
        pollTimer = null;
    }

    // Poll for new messages every 5 s while the meeting is active. The initial
    // state comes from PHP; later changes arrive via the session-state poller.
    if (running) {
        startPolling();
    }
    document.addEventListener('bbb:state', function (e) {
        running = e.detail.running ? 1 : 0;
        if (running && !document.hidden) {
            startPolling();
            refreshMessages(true);
        } else if (!running) {
            stopPolling();
        }
    });

    // Stop polling when page visibility changes (tab hidden)
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopPolling();
        } else if (running) {
            startPolling();
        }
    });
})();
JS, \yii\web\View::POS_READY);
?>
