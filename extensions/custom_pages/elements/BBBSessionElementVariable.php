<?php

namespace k7zz\humhub\bbb\extensions\custom_pages\elements;

use humhub\modules\custom_pages\modules\template\elements\BaseContentRecordElementVariable;
use humhub\modules\custom_pages\modules\template\elements\BaseRecordElementVariable;
use k7zz\humhub\bbb\models\Session;
use yii\db\ActiveRecord;

class BBBSessionElementVariable extends BaseContentRecordElementVariable
{
    public string $title;
    public string $description;
    public string $sessionUrl;
    public string $layout;
    public bool $enabled;
    public bool $publicJoin;
    public bool $showInSidebar;
    public bool $isSpaceDefault;
    public bool $allowRecording;

    public function setRecord(?ActiveRecord $record): BaseRecordElementVariable
    {
        if ($record instanceof Session) {
            $this->title = $record->title ?? '';
            $this->description = $record->description ?? '';
            $this->sessionUrl = $record->getUrl();
            $this->layout = $record->layout ?? '';
            $this->enabled = (bool) $record->enabled;
            $this->publicJoin = (bool) $record->public_join;
            $this->showInSidebar = (bool) $record->show_in_sidebar;
            $this->isSpaceDefault = (bool) $record->is_space_default;
            $this->allowRecording = (bool) $record->allow_recording;
        }

        return parent::setRecord($record);
    }
}
