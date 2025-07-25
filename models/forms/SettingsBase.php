<?php
namespace k7zz\humhub\bbb\models\forms;

use yii\base\Model;
use Yii;

/**
 * Base class for BBB settings forms (global and container-specific).
 *
 * Provides common properties and logic for loading and saving settings.
 *
 * @property string $navItemLabel  The label for the navigation item
 * @property bool   $addNavItem    Whether to add a navigation item
 */
abstract class SettingsBase extends Model
{
    /** @var mixed Settings storage (HumHub settings component) */
    protected $settings;

    public string $navItemLabel;
    public bool $addNavItem;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['navItemLabel'], 'string'],
            [['addNavItem'], 'boolean'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'addNavItem' => Yii::t('BbbModule.config', 'Add navigation item'),
            'navItemLabel' => Yii::t('BbbModule.config', 'Navigation item title'),
        ];
    }

    /* ========== Laden & Speichern in HumHub-Settings ========== */

    /**
     * Loads values from the config table.
     */
    public function init()
    {
        parent::init();
        // First call children init() to ensure settings are initialized
        $this->addNavItem = $this->settings->get('addNavItem') ?? true;
        $this->navItemLabel = $this->settings->get('navItemLabel') ?? 'Live Sessions';
    }

    /**
     * Saves values to the config table after validation.
     * @return bool
     */
    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }
        $this->settings->set('addNavItem', $this->addNavItem);
        $this->settings->set('navItemLabel', $this->navItemLabel);
        return true;
    }
}
