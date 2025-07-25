<?php
/**
 * Widget view: List of BBB session recordings.
 *
 * @var string $ajaxUrl         The AJAX URL to fetch recordings
 * @var string $sessionId       The session ID
 * @var bool $canAdminister     Whether the user can administer recordings
 */

use humhub\modules\ui\icon\widgets\Icon;
use yii\helpers\Html;
use yii\web\View;
use yii\helpers\Url;

/** @var string $ajaxUrl */
/** @var string $sessionId */
/** @var bool $canAdminister */

$containerId = 'bbb-recordings-' . $sessionId;
?>

<div id="<?= $containerId ?>" class="bbb-recording-container">
</div>

<?php
$text = Yii::t('BbbModule.base', 'Recordings');
$emptyText = Yii::t("BbbModule.base", "No recordings available");
$durationLabel = Yii::t("BbbModule.base", "Duration");
$canAdministerString = $canAdminister ? 'true' : 'false';
$iconDepublish = Icon::get('eye-slash');
$iconPublish = Icon::get('eye');
$urlPublishToggle = Url::to(['session/publish-recording']);
$titleDepublish = Yii::t('BbbModule.base', 'Depublish recording');
$titlePublish = Yii::t('BbbModule.base', 'Publish recording');
$confirmDepublish = Yii::t('BbbModule.base', 'Are you sure you want to depublish this recording?');
$confirmPublish = Yii::t('BbbModule.base', 'Are you sure you want to publish this recording?');
$dL = Html::a('<i class="fa fa-eye-slash" aria-hidden="true"></i>', [
    '/bbb/session/publish-recording',
    'recordId' => 'XXX',
    'publish' => false,
], [
    'class' => 'btn btn-warning btn-xs',
    'title' => 'Aufzeichnung depublizieren',
    'data-method' => 'post',
    'data-confirm' => 'Sind Sie sicher, dass Sie diese Aufzeichnung depublizieren möchten?',
    'aria-label' => 'Aufzeichnung depublizieren',
]);
$this->registerJs(<<<JS
$.getJSON('{$ajaxUrl}', function(data) {
    var container = $('#$containerId');
    container.empty();

    if (!data.length) {
        container.append('<p>{$emptyText}</p>');
        return;
    }
    container.append('<h6>{$text}</h6>');

    data.forEach(function(rec) {
        var html = '<div>';
        // If the recording has a URL, create a link
        if (rec.url) {
            html += `<a href="\${rec.url}" target="_blank" class="">▶️ </a>`;
        }
        // Add details
        html += `
                \${rec . date}, \${rec . time}, \${rec . duration}
        `;
        html += '{$dL}';
        if ('{$canAdministerString}' === 'true') {  
            if (rec.isPublished) {
                html += `
                    <a class="btn btn-warning btn-xs k7zz-ajax-button" 
                       id="bbb-recording-link-depublish-\${rec.id}"
                       title="{$titleDepublish}"
                       href="#"
                       data-action-url="{$urlPublishToggle}"
                       data-action="ajax"
                       data-record-id="\${rec.id}"
                       data-publish="false"
                       data-confirm="{$confirmDepublish}">{$iconDepublish}</a>`;
            } else {
                html += `
                    <a class="btn btn-success btn-xs k7zz-ajax-button"
                       id="bbb-recording-link-publish-\${rec.id}"
                       title="{$titlePublish}"
                       href="#"
                       data-action-url="{$urlPublishToggle}"
                       data-action="ajax"
                       data-record-id="\${rec.id}"
                       data-publish="true"
                       data-confirm="{$confirmPublish}">{$iconPublish}</a>`;
            }
        }   

        html += '</div>';
        container.append(html);
    });


    $('[data-action="ajax"]').on('click', function(e) {
        const button = $(e.currentTarget);
        const recordId = button.data('record-id');
        const publish = button.data('publish');
        const url = button.data('action-url');
        
        $.post(url, {recordId: recordId, publish: publish, _csrf: yii.getCsrfToken()}, function(response) {
            if (response.success) {
                // Update the button state
                if (publish) {
                    button
                        .removeClass('btn-success')
                        .addClass('btn-warning')
                        .attr('data-publish', 'false')
                        .attr('id', 'bbb-recording-link-depublish-' + recordId)
                        .attr('title', '{$titleDepublish}')
                        .html('{$iconDepublish}');
                } else {    
                    button
                        .removeClass('btn-warning')
                        .addClass('btn-success')
                        .attr('data-publish', 'true')
                        .attr('id', 'bbb-recording-link-publish-' + recordId)
                        .attr('title', '{$titlePublish}')
                        .html('{$iconPublish}');
                }
                alert(response.message || 'Recording status updated successfully.');
            } else {
                alert(response.message || 'An error occurred.');
            }
        }).fail(function() {
            alert('Failed to update recording status.');
        });
    });
});


JS, View::POS_READY);
?>