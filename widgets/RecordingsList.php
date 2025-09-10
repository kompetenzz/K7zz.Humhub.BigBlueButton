<?php
namespace k7zz\humhub\bbb\widgets;
use Yii\web\View;
use humhub\components\Widget;
use humhub\libs\Html;
use Yii;
use yii\helpers\Url;

/**
 * Widget: List of BBB session recordings.
 *
 * Renders a list of recordings for a given session, optionally with admin controls.
 *
 * @property int $sessionId                The session ID
 * @property mixed $contentContainer       The content container (space/user) or null
 * @property bool $canAdminister           Whether the user can administer recordings
 */
class RecordingsList extends Widget
{
    private const AJAX_URL = '/bbb/session/recordings';
    public $sessionId;
    public $contentContainer;
    public $canAdminister = false; // Whether the user can administer recordings

    public function run(): string
    {
        $containerId = 'bbb-recordings-' . $this->sessionId;
        $ctxUrl = $this->contentContainer ? $this->contentContainer->createUrl(self::AJAX_URL) : self::AJAX_URL;
        $ajaxUrl = Url::to([$ctxUrl, 'id' => $this->sessionId]);
        $errTxt = Yii::t('BbbModule.base', 'Error loading recordings');

        return $this->render('recordingsList', [
            'containerId' => $containerId,
            'ajaxUrl' => $ajaxUrl,
            'errTxt' => $errTxt,
            'canAdminister' => $this->canAdminister,
            'sessionId' => $this->sessionId
        ]);
    }
}
