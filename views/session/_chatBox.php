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

$queueUrl    = $routeBase('/bbb/session/queue-chat', ['id' => $session->id]);
$messagesUrl = $routeBase('/bbb/session/pre-meeting-chats', ['id' => $session->id]);

?>
<div class="card-footer bbb-chat-box" id="bbb-chat-box-<?= $session->id ?>">
    <h6 class="mb-2">
        <?= Icon::get('comment') ?> <?= Yii::t('BbbModule.base', 'Pre-meeting chat') ?>
        <?php if ($running): ?>
            <span class="badge bg-success ms-1"><?= Yii::t('BbbModule.base', 'Meeting active') ?></span>
        <?php endif; ?>
    </h6>

    <div class="bbb-chat-messages mb-2" id="bbb-chat-messages-<?= $session->id ?>">
        <?= $this->renderFile('@bbb/views/session/_chatMessages.php', ['messages' => $messages]) ?>
    </div>

    <?php if ($running): ?>
        <p class="text-muted small mb-0">
            <?= Icon::get('info-circle') ?>
            <?= Yii::t('BbbModule.base', 'The meeting is running. Join BBB to chat in real time. Messages sent here will appear in the next meeting.') ?>
        </p>
    <?php else: ?>
        <div class="bbb-chat-form">
            <div class="input-group">
                <textarea id="bbb-chat-input-<?= $session->id ?>"
                    class="form-control form-control-sm"
                    rows="2"
                    placeholder="<?= Html::encode(Yii::t('BbbModule.base', 'Write a message… (e.g. "I\'ll be a bit late")')) ?>"
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
    <?php endif; ?>
</div>

<?php
$id           = $session->id;
$sendLabel    = Html::encode(Yii::t('BbbModule.base', 'Send'));
$errEmpty     = Html::encode(Yii::t('BbbModule.base', 'Please enter a message.'));
$errSend      = Html::encode(Yii::t('BbbModule.base', 'Could not send message. Please try again.'));
$successLabel = Html::encode(Yii::t('BbbModule.base', 'Message queued.'));

$this->registerJs(<<<JS
(function () {
    var \$btn   = \$('.bbb-chat-send[data-session-id="{$id}"]');
    var \$input = \$('#bbb-chat-input-{$id}');
    var \$msgs  = \$('#bbb-chat-messages-{$id}');
    var \$fb    = \$('#bbb-chat-feedback-{$id}');

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
                refreshMessages(\$btn.data('messages-url'));
            } else {
                showFeedback(res.error || '{$errSend}', 'text-danger');
            }
        }).fail(function () {
            showFeedback('{$errSend}', 'text-danger');
        }).always(function () {
            \$btn.prop('disabled', false);
        });
    }

    function refreshMessages(url) {
        \$.get(url).done(function(html) { \$msgs.html(html); });
    }

    function showFeedback(msg, cls) {
        \$fb.removeClass('text-danger text-success').addClass(cls).text(msg).show();
        setTimeout(function() { \$fb.fadeOut(); }, 3000);
    }

    \$btn.on('click', sendMessage);
    \$input.on('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
})();
JS, \yii\web\View::POS_READY);
?>
