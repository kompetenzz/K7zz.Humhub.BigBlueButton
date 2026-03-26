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
     * Sort order of the BBB sidebar widget within the space right-column sidebar.
     * Lower values appear higher up. HumHub default for unsorted widgets is 9000.
     * Reference values: Activities = 10, Members = 30.
     *
     * @var int
     */
    public int $sidebarSortOrder = 1;

    /**
     * @inheritdoc
     * Initializes settings for the given content container.
     */
    public function init()
    {
        $this->settings = Yii::$app->getModule('bbb')->settings->contentContainer($this->contentContainer);
        parent::init();
        $this->sidebarSortOrder = (int)($this->settings->get('sidebarSortOrder') ?? 1);
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            ['sidebarSortOrder', 'integer', 'min' => 1, 'max' => 9000],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'sidebarSortOrder' => Yii::t('BbbModule.config', 'Sidebar sort order'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function save(): bool
    {
        if (!parent::save()) {
            return false;
        }
        $this->settings->set('sidebarSortOrder', $this->sidebarSortOrder);
        return true;
    }
}
