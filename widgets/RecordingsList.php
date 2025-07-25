<?php
namespace k7zz\humhub\bbb\widgets;
use humhub\components\Widget;
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

    public function run()
    {
        return $this->render('recordingsList', [
            'sessionId' => $this->sessionId,
            'canAdminister' => $this->canAdminister,
            'ajaxUrl' => $this->contentContainer
                ? $this->contentContainer->createUrl(self::AJAX_URL, ['id' => $this->sessionId])
                : Url::to([self::AJAX_URL, 'id' => $this->sessionId])
        ]);
    }
}
