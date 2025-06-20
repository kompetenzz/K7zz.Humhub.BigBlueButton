<?php
namespace k7zz\humhub\bbb\models\forms;

use yii\base\Model;
use Yii;

/**
 * Globale BBB-Moduleinstellungen.
 *
 * Wird im ConfigController geladen / gespeichert:
 *   $model = new SettingsForm();
 *   if ($model->load($_POST) && $model->save()) …
 */
class SettingsForm extends SettingsBase
{
    public string $bbbUrl = '';

    /** @var string  Sicherheitssalt / Secret aus der BBB-Konfiguration */
    public string $bbbSecret = '';

    public function rules(): array
    {
        return array_merge([
            [['bbbUrl', 'bbbSecret'], 'required'],
            ['bbbUrl', 'url', 'defaultScheme' => 'https'],
            ['bbbSecret', 'string', 'max' => 255],
        ], parent::rules());
    }

    public function attributeLabels(): array
    {
        return array_merge([
            'bbbUrl' => Yii::t('BbbModule.config', 'Server URL'),
            'bbbSecret' => Yii::t('BbbModule.config', 'Shared Secret')
        ], parent::attributeLabels());
    }

    /* ========== Laden & Speichern in HumHub-Settings ========== */

    /** Lädt Werte aus Config-Tabelle */
    public function init()
    {
        $this->settings = Yii::$app->getModule('bbb')->settings;
        parent::init();
        $this->bbbUrl = $this->settings->get('bbbUrl') ?? '';
        $this->bbbSecret = $this->settings->get('bbbSecret') ?? '';
    }

    /** speichert bei erfolgreicher Validierung */
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
