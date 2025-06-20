<?php
namespace k7zz\humhub\bbb\models\forms;

use humhub\modules\content\components\ContentContainerActiveRecord;
use Yii;

/**
 * Container BBB-Moduleinstellungen.
 *
 */
class ContainerSettingsForm extends SettingsBase
{
    /**
     * @var ContentContainerActiveRecord
     */
    public $contentContainer;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->settings = Yii::$app->getModule('bbb')->settings->contentContainer($this->contentContainer);

        parent::init();
    }

}
