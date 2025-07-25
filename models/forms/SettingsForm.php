<?php
namespace k7zz\humhub\bbb\models\forms;

use yii\base\Model;
use Yii;

/**
 * Form model for global BBB module settings.
 *
 * Used in ConfigController to load and save global settings like server URL and secret.
 *
 * Example usage:
 *   $model = new SettingsForm();
 *   if ($model->load($_POST) && $model->save()) ...
 *
 * @property string $bbbUrl     The BBB server URL
 * @property string $bbbSecret  The shared secret for BBB
 */
class SettingsForm extends SettingsBase
{
    public string $bbbUrl = '';
    /** @var string  Security salt / secret from BBB configuration */
    public string $bbbSecret = '';

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return array_merge([
            [['bbbUrl', 'bbbSecret'], 'required'],
            ['bbbUrl', 'url', 'defaultScheme' => 'https'],
            ['bbbSecret', 'string', 'max' => 255],
        ], parent::rules());
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return array_merge([
            'bbbUrl' => Yii::t('BbbModule.config', 'Server URL'),
            'bbbSecret' => Yii::t('BbbModule.config', 'Shared Secret')
        ], parent::attributeLabels());
    }

    /* ========== Laden & Speichern in HumHub-Settings ========== */

    /**
     * Loads values from the config table.
     */
    public function init()
    {
        $this->settings = Yii::$app->getModule('bbb')->settings;
        parent::init();
        $this->bbbUrl = $this->settings->get('bbbUrl') ?? '';
        $this->bbbSecret = $this->settings->get('bbbSecret') ?? '';
    }

    /**
     * Saves values to the config table after validation.
     * @return bool
     */
    public function save(): bool
    {
        if (!parent::save()) {
            return false;
        }
        if (!$this->validate()) {
            return false;
        }
        $this->settings->set('bbbUrl', $this->bbbUrl);
        $this->settings->set('bbbSecret', $this->bbbSecret);
        return true;
    }
}
