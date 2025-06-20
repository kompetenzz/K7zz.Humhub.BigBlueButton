<?php
namespace k7zz\humhub\bbb\models\forms;

use yii\base\Model;
use Yii;

abstract class SettingsBase extends Model
{
    protected $settings;

    public string $navItemLabel;

    public bool $addNavItem;

    public function rules(): array
    {
        return [
            [['navItemLabel'], 'string'],
            [['addNavItem'], 'boolean'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'addNavItem' => Yii::t('BbbModule.config', 'Add navigation item'),
            'navItemLabel' => Yii::t('BbbModule.config', 'Navigation item title'),
        ];
    }

    /* ========== Laden & Speichern in HumHub-Settings ========== */

    /** Lädt Werte aus Config-Tabelle */
    public function init()
    {
        parent::init();
        // First call children init() to ensure settings are initialized
        $this->addNavItem = $this->settings->get('addNavItem') ?? true;
        $this->navItemLabel = $this->settings->get('navItemLabel') ?? 'Live Sessions';
    }

    /** speichert bei erfolgreicher Validierung */
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
