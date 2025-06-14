<?php
namespace humhub\modules\bbb\models\forms;

use humhub\modules\bbb\models\SessionUser;
use humhub\modules\file\converter\PreviewImage;
use humhub\modules\file\models\File;
use yii\web\UploadedFile;
use yii\base\Model;
use Yii;
use humhub\modules\bbb\models\Session;
use humhub\modules\user\models\User;
use yii\helpers\Inflector;
use yii\web\NotFoundHttpException;

/**
 * Formular-Model für Create / Update einer BBB-Session.
 *
 * Verwendet im SessionController:
 *
 *   $form = SessionForm::create($containerId);     // neu
 *   // oder
 *   $form = SessionForm::edit($id, $containerId);  // bearbeiten
 *
 *   if ($form->load($_POST) && $form->save()) …
 */
class SessionForm extends Model
{
    /* ---------- Form-Attribute ---------- */
    public ?int $id = null;
    public string $name = '';
    public ?string $title = null;
    public ?string $description = null;
    public string $moderator_pw = '';
    public string $attendee_pw = '';
    public $attendeeRefs = [];
    public $moderatorRefs = [];

    /* interne Helfer */
    private ?Session $record = null;
    public int $containerId;
    private int $creatorId;
    public bool $publicJoin = true;
    public bool $publicModerate = true;
    public bool $enabled = true;
    public ?int $image_file_id = null;        // hier speichern wir später die File‐ID
    public $image = null;
    public ?UploadedFile $imageUpload = null;
    public $previewImage = null; // für die Thumbnail-Vorschau

    public function init()
    {
        parent::init();

        // Nur beim Anlegen (id == null) vorschlagen
        if ($this->id === null) {
            $this->moderator_pw = Yii::$app->security->generateRandomString(10);
            $this->attendee_pw = Yii::$app->security->generateRandomString(10);
        }
    }

    /* ---------- Fabrik-Methoden ---------- */

    /** Neues Formular für eine frische Session */
    public static function create(int $containerId): self
    {
        $model = new self();
        $model->containerId = $containerId;
        $model->creatorId = Yii::$app->user->id;
        // Default-Werte
        $model->moderator_pw = Yii::$app->security->generateRandomString(10);
        $model->attendee_pw = Yii::$app->security->generateRandomString(10);
        return $model;
    }

    /** Formular zum Bearbeiten */
    public static function edit(?int $id = null, ?string $slug = null, ?int $containerId = null): self
    {
        if ($id === null && $slug === null) {
            return self::create($containerId);
        }
        $record = $id !== null ? Session::findOne($id)
            : Session::find()->where(['name' => $slug, 'deleted_at' => null])->one()
            ?? throw new NotFoundHttpException();

        if ($containerId !== null && $record->contentcontainer_id != $containerId) {
            throw new NotFoundHttpException();          // fremder Container
        }

        $userQ = SessionUser::find()
            ->join('JOIN', User::tableName(), 'user_id = user.id')
            ->where(['session_id' => $record->id]);

        $model = new self();
        $model->record = $record;
        $model->id = $record->id;
        $model->name = $record->name;
        $model->title = $record->title;
        $model->description = $record->description;
        $model->moderator_pw = $record->moderator_pw;
        $model->attendee_pw = $record->attendee_pw;
        $model->attendeeRefs = $userQ
            ->andWhere(['role' => 'attendee'])
            ->select('user.guid')
            ->column();
        $model->moderatorRefs = $userQ
            ->andWhere(['role' => 'moderator'])
            ->select('user.guid')
            ->column();
        $model->publicJoin = $record->getAttendeeUsers() === null;
        $model->publicModerate = $record->getModeratorUsers() === null;
        $model->containerId = $record->contentcontainer_id ?? 0;
        $model->creatorId = $record->creator_user_id;
        if ($record->image_file_id !== null) {
            $model->image_file_id = $record->image_file_id;
            $model->image = $record->getImageFile()->one(); // Lazy-Loading des Bildes
            $previewImage = new PreviewImage();
            if ($previewImage->applyFile($model->image)) {
                $model->previewImage = $previewImage; // Vorschau-Bild für die Thumbnail-Anzeige
            }
        }
        return $model;
    }

    public function beforeValidate(): bool
    {
        if ($this->name === '' && $this->title !== null) {
            // z. B.  "Daily Stand-up"  →  "daily-stand-up"
            $this->name = Inflector::slug($this->title);
        }

        return parent::beforeValidate();
    }

    public function rules(): array
    {
        return [
            [['title', 'moderator_pw', 'attendee_pw'], 'required'],
            [['name', 'title', 'moderator_pw', 'attendee_pw'], 'string', 'max' => 255],
            ['name', 'unique', 'targetClass' => Session::class, 'targetAttribute' => 'name', 'on' => 'create'],
            [['description'], 'string'],
            [['attendeeRefs', 'moderatorRefs'], 'each', 'rule' => ['integer']],
            ['image_file_id', 'integer'],
            ['image', 'image', 'extensions' => 'png, jpg, jpeg', 'minWidth' => 200, 'minHeight' => 200],
        ];
    }

    public function load($data, $formName = null): bool
    {
        $r = parent::load($data, $formName);
        $file = UploadedFile::getInstance($this, 'image');
        if ($file) {
            $this->imageUpload = $file;
        }
        return $r;
    }

    /* ---------- Speichern ---------- */
    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $session = $this->record ?? new Session([
            'uuid' => uniqid('bbb-sess-'),
            'creator_user_id' => $this->creatorId,
            'contentcontainer_id' => $this->containerId ?: null,
            'created_at' => time(),
        ]);

        $session->id = $this->id;
        $session->name = $this->name;
        $session->title = $this->title;
        $session->description = $this->description;
        $session->moderator_pw = $this->moderator_pw;
        $session->attendee_pw = $this->attendee_pw;
        $session->enabled = $this->enabled;
        $session->updated_at = time();

        if ($this->imageUpload instanceof UploadedFile) {
            if ($session->image_file_id) {
                $humhubFile = $session->getImageFile()->one();
            } else {
                $humhubFile = new File();
            }
            $humhubFile->file_name = $this->imageUpload->baseName . '.' . $this->imageUpload->extension;
            $humhubFile->mime_type = $this->imageUpload->type;
            $humhubFile->size = $this->imageUpload->size;

            if (!$humhubFile->save()) {
                // Wenn die File‐Metadaten nicht gespeichert werden können, abbrechen
                $this->addError('image', Yii::t('BbbModule.base', 'Could not save image reference to database.'));
                return false;
            }
            // 3) Physisches Speichern in HumHubs Storage
            $humhubFile->setStoredFileContent(file_get_contents($this->imageUpload->tempName));
            if (!$session->image_file_id) {
                $session->image_file_id = $humhubFile->id;
            }
        }
        if (!$session->save())
            return false;

        $this->id = $session->id; // für die SessionUser-Zuordnung
        $this->record = $session; // für die SessionUser-Zuordnung

        // Assign moderators
        if (!$this->publicModerate) {
            $moderatorDBUsers = User::find()
                ->where(['IN', 'guid', $this->moderatorRefs])
                ->all();
            foreach ($moderatorDBUsers as $user) {
                $this->addUser($user, 'moderator');
            }
        } else if ($this->record !== null) {
            SessionUser::deleteAll(['session_id' => $this->record->id, 'role' => 'moderator']);
        }

        // Assign attendees
        if (!$this->publicJoin) {
            $attendeeDBUsers = User::find()
                ->where(['IN', 'guid', $this->attendeeRefs])
                ->andWhere(['NOT IN', 'guid', $this->moderatorRefs]) // keine Moderatoren als Teilnehmer
                ->all();
            foreach ($attendeeDBUsers as $user) {
                $this->addUser($user, $this->publicModerate ? 'moderator' : 'attendee');
            }
        } else if ($this->record !== null) {
            SessionUser::deleteAll(['session_id' => $this->record->id, 'can_join' => true, 'role' => 'attendee']);
        }
        return true;
    }

    private function addUser(User $user, string $role): void
    {
        if (
            SessionUser::find()
                ->where(['session_id' => $this->record->id, 'user_id' => $user->id])
                ->exists()
        ) {
            return; // existiert schon
        }

        (new SessionUser([
            'session_id' => $this->record->id,
            'user_id' => $user->id,
            'role' => $role,
            'can_start' => $role === 'moderator',
            'can_join' => true,
            'created_at' => time(),
        ]))->save();
    }
}
