<?php
namespace k7zz\humhub\bbb\widgets;
use humhub\components\Widget;
use yii\helpers\Url;

class RecordingsList extends Widget
{
    private const AJAX_URL = '/bbb/session/recordings';
    public $sessionId;
    public $contentContainer;

    public function run()
    {
        return $this->render('recordingsList', [
            'sessionId' => $this->sessionId,
            'ajaxUrl' => $this->contentContainer
                ? $this->contentContainer->createUrl(self::AJAX_URL, ['id' => $this->sessionId])
                : Url::to([self::AJAX_URL, 'id' => $this->sessionId])
        ]);
    }
}
