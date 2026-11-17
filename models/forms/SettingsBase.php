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
 * @property int    $navItemSortOrder  Sort order of the navigation item
 */
abstract class SettingsBase extends Model
{
    public const DEFAULT_NAV_ITEM_SORT_ORDER = 1000;

    /** @var mixed Settings storage (HumHub settings component) */
    protected $settings;

    public string $navItemLabel;
    public bool $addNavItem;
    /**
     * Sort order of the navigation item. Lower values appear further up/left.
     * Not typed as int, since an empty or non-numeric form value must reach the validators.
     *
     * @var int|string
     */
    public $navItemSortOrder = self::DEFAULT_NAV_ITEM_SORT_ORDER;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['navItemLabel'], 'string'],
            [['addNavItem'], 'boolean'],
            ['navItemSortOrder', 'default', 'value' => self::DEFAULT_NAV_ITEM_SORT_ORDER],
            ['navItemSortOrder', 'integer', 'min' => 1, 'max' => 99999],
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
            'navItemSortOrder' => Yii::t('BbbModule.config', 'Navigation item sort order'),
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
        $this->navItemSortOrder = (int) ($this->settings->get('navItemSortOrder') ?? self::DEFAULT_NAV_ITEM_SORT_ORDER);
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
        $this->settings->set('navItemSortOrder', (int) $this->navItemSortOrder);
        return true;
    }
}
