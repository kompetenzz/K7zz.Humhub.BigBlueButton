<?php
namespace k7zz\humhub\bbb\widgets;

use humhub\modules\content\widgets\stream\WallStreamModuleEntryWidget as BaseWallEntry;
use humhub\modules\user\models\Session;

/**
 * @property Session $model              The session model
 * @property bool $running                 Whether the session is currently running
 * @property mixed $contentContainer       The content container (space/user) or null
 */
class WallEntry extends BaseWallEntry
{
    public $model; // k7zz\humhub\bbb\models\Session
    public bool $running;
    public $contentContainer = null;

    protected function renderContent()
    {
        return $this->render('wallEntry', ['model' => $this->model]);
    }

    protected function getIcon()
    {
        // By default we do not have to overwrite this function unless we want to overwrite the default ContentActiveRecord::$icon
        return 'video-camera';
    }

    protected function getTitle()
    {
        return $this->model->title;
    }
}