<?php
namespace k7zz\humhub\bbb\models\forms;

use humhub\modules\content\components\ContentContainerActiveRecord;
use Yii;

/**
 * Form model for container-specific BBB module settings.
 *
 * Used to configure BBB settings for a specific content container (space or user).
 *
 * @property ContentContainerActiveRecord $contentContainer
 */
class ContainerSettingsForm extends SettingsBase
{
    /**
     * @var ContentContainerActiveRecord The content container for which settings are managed.
     */
    public $contentContainer;

    /**
     * @inheritdoc
     * Initializes settings for the given content container.
     */
    public function init()
    {
        $this->settings = Yii::$app->getModule('bbb')->settings->contentContainer($this->contentContainer);
        parent::init();
    }
}
