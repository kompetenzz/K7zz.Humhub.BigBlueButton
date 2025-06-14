<?php
namespace humhub\modules\bbb\models\forms;

use yii\base\Model;
use Yii;

/**
 * Globale BBB-Moduleinstellungen.
 *
 * Wird im ConfigController geladen / gespeichert:
 *   $model = new SettingsForm();
 *   if ($model->load($_POST) && $model->save()) …
 */
class SettingsForm extends Model
{
    private $settings;

    public string $bbbUrl = '';

    /** @var string  Sicherheitssalt / Secret aus der BBB-Konfiguration */
    public string $bbbSecret = '';

    public function rules(): array
    {
        return [
            [['bbbUrl', 'bbbSecret'], 'required'],
            ['bbbUrl', 'url', 'defaultScheme' => 'https'],
            ['bbbSecret', 'string', 'max' => 255],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'bbbUrl' => Yii::t('BbbModule.base', 'BBB Server URL'),
            'bbbSecret' => Yii::t('BbbModule.base', 'BBB Shared Secret'),
        ];
    }

    /* ========== Laden & Speichern in HumHub-Settings ========== */

    /** Lädt Werte aus Config-Tabelle */
    public function init()
    {
        parent::init();
        $this->settings = Yii::$app->getModule('bbb')->settings;
        $this->bbbUrl = $this->settings->get('bbbUrl') ?? '';
        $this->bbbSecret = $this->settings->get('bbbSecret') ?? '';
    }

    /** speichert bei erfolgreicher Validierung */
    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }
        $this->settings->set('bbbUrl', $this->bbbUrl);
        $this->settings->set('bbbSecret', $this->bbbSecret);
        return true;
    }
}
