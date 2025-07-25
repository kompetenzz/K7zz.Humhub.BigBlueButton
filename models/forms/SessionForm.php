<?php
namespace k7zz\humhub\bbb\models\forms;

use humhub\modules\content\components\ContentContainerActiveRecord;
use k7zz\humhub\bbb\models\SessionUser;
use humhub\modules\file\converter\PreviewImage;
use humhub\modules\file\models\File;
use yii\web\UploadedFile;
use yii\base\Model;
use Yii;
use k7zz\humhub\bbb\models\Session;
use humhub\modules\user\models\User;
use yii\helpers\Inflector;
use yii\web\NotFoundHttpException;

/**
 * Form model for creating and updating BBB sessions.
 *
 * Used in SessionController for both creation and editing of sessions.
 * Handles validation, saving, and image upload logic.
 *
 * Example usage:
 *   $form = SessionForm::create($containerId);     // new
 *   $form = SessionForm::edit($id, $containerId);  // edit
 *   if ($form->load($_POST) && $form->save()) ...
 */
class SessionForm extends Model
{
    public const SLUG_PATTERN = '[a-z0-9\-]+';
    /* ---------- Form attributes ---------- */
    public ?int $id = null;
    public string $name = '';
    public ?string $title = null;
    public ?string $description = null;
    public string $moderator_pw = '';
    public string $attendee_pw = '';
    public $attendeeRefs = [];
    public $moderatorRefs = [];

    /* Internal helpers */
    private ?Session $record = null;
    public ?ContentContainerActiveRecord $contentContainer;
    private int $creatorId;
    public bool $publicModerate = true;
    public bool $publicJoin = true;
    public bool $joinCanStart = true;
    public bool $joinCanModerate = false;
    public bool $hasWaitingRoom = false;
    public bool $allowRecording = true;
    public bool $muteOnEntry = false;
    public bool $enabled = true;
    public ?int $image_file_id = null;
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
    public static function create(ContentContainerActiveRecord $container = null): self
    {
        $model = new self();
        $model->contentContainer = $container;
        $model->creatorId = Yii::$app->user->id;
        // Default-Werte
        $model->moderator_pw = Yii::$app->security->generateRandomString(10);
        $model->attendee_pw = Yii::$app->security->generateRandomString(10);
        return $model;
    }

    private static function getUserQuery(Session $session)
    {
        return SessionUser::find()
            ->join('JOIN', User::tableName(), 'user_id = user.id')
            ->where(['session_id' => $session->id]);
    }

    /** Formular zum Bearbeiten */
    public static function edit(Session $session): self
    {
        if ($session === null) {
            throw new NotFoundHttpException(Yii::t('BbbModule.base', 'Session not found.'));
        }
        if ($session->deleted_at !== null) {
            throw new NotFoundHttpException(Yii::t('BbbModule.base', 'Session with Id {id} not found.', ['id' => $session->id]));
        }
        if (!$session->canAdminister()) {
            throw new NotFoundHttpException(Yii::t('BbbModule.base', 'Session with Id {id} not found.', ['id' => $session->id]));
        }

        $model = new self();
        $model->record = $session;
        $model->id = $session->id;
        $model->name = $session->name;
        $model->title = $session->title;
        $model->description = $session->description;
        $model->moderator_pw = $session->moderator_pw;
        $model->attendee_pw = $session->attendee_pw;
        $model->attendeeRefs = self::getUserQuery($session)
            ->andWhere(['role' => 'attendee'])
            ->select('user.guid')
            ->column();
        $model->moderatorRefs = self::getUserQuery($session)
            ->andWhere(['role' => 'moderator'])
            ->select('user.guid')
            ->column();
        $model->publicJoin = count($model->attendeeRefs) === 0;
        $model->publicModerate = count($model->moderatorRefs) === 0;
        $model->joinCanStart = $session->join_can_start;
        $model->joinCanModerate = $session->join_can_moderate;
        $model->hasWaitingRoom = $session->has_waitingroom;
        $model->allowRecording = $session->allow_recording;
        $model->muteOnEntry = $session->mute_on_entry;
        $model->contentContainer = $session->content->container;
        $model->creatorId = $session->creator_user_id;
        if ($session->image_file_id !== null) {
            $model->image_file_id = $session->image_file_id;
            $model->image = $session->getImageFile()->one(); // Lazy-Loading des Bildes
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
            [
                'name',
                'unique',
                'targetClass' => Session::class,
                'targetAttribute' => 'name',
                'when' => fn() => $this->id === null,
                'message' => Yii::t('BbbModule.base', 'This name has already been taken.'),
            ],
            [
                'name',
                'unique',
                'targetClass' => Session::class,
                'targetAttribute' => 'name',
                'when' => fn() => $this->id > 0,
                'message' => Yii::t('BbbModule.base', 'This name has already been taken.'),
                'filter' => function ($query) {
                    return $query->andWhere(['!=', 'id', $this->id]);
                }
            ],
            ['name', 'match', 'pattern' => '/^' . self::SLUG_PATTERN . '$/', 'message' => Yii::t('BbbModule.base', 'Only lowercase letters, numbers and hyphens are allowed.')],
            [['description'], 'string'],
            [['attendeeRefs', 'moderatorRefs'], 'each', 'rule' => ['string']],
            ['image_file_id', 'integer'],
            ['image', 'image', 'extensions' => 'png, jpg, jpeg', 'minWidth' => 200, 'minHeight' => 200],
            [['joinCanStart', 'joinCanModerate', 'hasWaitingRoom', 'allowRecording', 'muteOnEntry', 'enabled'], 'boolean']
        ];
    }

    public function load($data, $formName = null): bool
    {
        $result = parent::load($data, $formName);
        $file = UploadedFile::getInstance($this, 'image');
        if ($file) {
            $this->imageUpload = $file;
        }
        return $result;
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
            'created_at' => time(),
        ]);
        if ($this->contentContainer !== null) {
            $session->content->container = $this->contentContainer;
        }

        echo "Saving session with Image: {$session->image_file_id}\n"; // Debug-Ausgabe

        $session->id = $this->id;
        $session->name = $this->name;
        $session->title = $this->title;
        $session->description = $this->description;
        $session->moderator_pw = $this->moderator_pw;
        $session->attendee_pw = $this->attendee_pw;
        $session->join_can_start = $this->joinCanStart;
        $session->join_can_moderate = $this->joinCanModerate;
        $session->has_waitingroom = $this->hasWaitingRoom;
        $session->allow_recording = $this->allowRecording;
        $session->mute_on_entry = $this->muteOnEntry;
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
            $humhubFile->setStoredFileContent(file_get_contents($this->imageUpload->tempName));

            if (!$session->image_file_id) {
                $session->image_file_id = $humhubFile->id;
                $session->fileManager->attach($humhubFile->guid);
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

    private function addUser(User $user, string $role): bool
    {
        $s = SessionUser::find()
            ->where(['session_id' => $this->record->id, 'user_id' => $user->id])
            ->one();
        if ($s === null) {
            $s = new SessionUser([
                'session_id' => $this->record->id,
                'user_id' => $user->id,
                'created_at' => time()
            ]);
        }
        $s->role = $role;
        $s->can_start = $role === 'moderator';
        $s->can_join = true;

        return $s->save();
    }
}
