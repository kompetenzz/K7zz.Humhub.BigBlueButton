<?php
namespace k7zz\humhub\bbb\models\forms;

use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\space\models\Space;
use k7zz\humhub\bbb\enums\Layouts;
use k7zz\humhub\bbb\helpers\Tools;
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
use humhub\modules\topic\models\Topic;

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
    public const AUTO_IMAGE_FORMAT = 'png';
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
    public $visibility;
    public $hidden;
    /**
     * @var
     */
    public $topics = [];
    public ?ContentContainerActiveRecord $contentContainer;
    private int $creatorId;
    public bool $moderateByPermissions = true;
    public bool $publicJoin = false;
    public bool $joinByPermissions = true;
    public bool $joinCanStart = false;
    public bool $joinCanModerate = false;
    public bool $hasWaitingRoom = false;
    public bool $allowRecording = true;
    public bool $muteOnEntry = false;
    public bool $enabled = true;
    public string $layout = Layouts::CUSTOM_LAYOUT;

    // pdf Presentation - an uploaded file in PDF format
    public ?int $presentation_file_id = null; // DB ref
    /** @var UploadedFile|string|null */
    public $presentationUpload = null; // Form upload
    public ?File $presentationFile = null; // DB record
    // Preview image der Presentation – a generated thumbnail image from the first page of the PDF
    public ?int $presentation_preview_file_id = null; // DB ref
    public ?File $presentationPreviewImageFile = null; // DB record
    public ?PreviewImage $presentationPreviewImage = null; // Thumbnail

    // Session image
    public ?int $image_file_id = null; // DB ref
    public ?File $imageFile = null; // DB record
    /** @var UploadedFile|string|null */
    public $imageUpload = null; // Form upload
    public ?PreviewImage $previewImage = null; // Thumbnail

    public function init()
    {
        parent::init();

        // Nur beim Anlegen (id == null) vorschlagen
        if ($this->id == null) {
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
        $model->setDefaultVisibility();
        return $model;
    }

    /**
     * @return int the default visibility of the given content container
     */
    private function setDefaultVisibility(): void
    {
        $this->hidden = true;
        if ($this->contentContainer && $this->contentContainer instanceof Space) {
            $this->visibility = $this->contentContainer->getDefaultContentVisibility();
        } else {
            $this->visibility = Content::VISIBILITY_PRIVATE;
        }
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
        $model->joinByPermissions = count($model->attendeeRefs) === 0;
        $model->moderateByPermissions = count($model->moderatorRefs) === 0;
        $model->publicJoin = $session->public_join;
        $model->joinCanStart = $session->join_can_start;
        $model->joinCanModerate = $session->join_can_moderate;
        $model->hasWaitingRoom = $session->has_waitingroom;
        $model->allowRecording = $session->allow_recording;
        $model->muteOnEntry = $session->mute_on_entry;
        $model->contentContainer = $session->content->container;
        $model->creatorId = $session->creator_user_id;
        $model->layout = $session->layout;
        $model->visibility = $session->content->visibility;
        $model->hidden = $session->content->hidden;
        $model->topics = $session->content->getTags(Topic::class);


        Yii::error("Loading pdf" . $session->presentation_file_id);
        // pdf and it's preview image
        if ($session->presentation_file_id > 0) {
            Yii::error("Loading pdf with id: " . $session->presentation_file_id, 'bbb');
            $model->presentation_file_id = $session->presentation_file_id;
            $model->presentationFile = $session->getPresentationFile(); // Lazy-Loading der Präsentation
            if ($session->presentation_preview_file_id > 0) {
                Yii::error("Loading presentation preview image with id: " . $session->presentation_preview_file_id, 'bbb');
                $model->presentationPreviewImageFile = $session->getPresentationPreviewImageFile(); // Lazy-Loading des Bildes
                Yii::error("Loaded " . $model->presentationPreviewImageFile->file_name . "-" . $model->presentationPreviewImageFile->getUrl(), 'bbb');
                $presentationPreviewImage = new PreviewImage();
                if ($presentationPreviewImage->applyFile($model->presentationPreviewImageFile)) {
                    Yii::error("Applied prese ntation preview image for session edit: " . ($model->presentationPreviewImageFile ? $model->presentationPreviewImageFile->getUrl() : 'no file'), 'bbb');
                    $model->presentationPreviewImage = $presentationPreviewImage; // Vorschau-Bild für die Thumbnail-Anzeige
                }
            }
        }
        Yii::error("Loading image file for session edit: " . ($session->image_file_id ? $session->image_file_id : 'no file'), 'bbb');
        if ($session->image_file_id > 0) {
            Yii::error("Loading image file for session edit: " . ($session->image_file_id ? $session->image_file_id : 'no file'), 'bbb');
            $model->image_file_id = $session->image_file_id;
            $model->imageFile = $session->getImageFile(); // Lazy-Loading des Bildes
            $previewImage = new PreviewImage();
            Yii::error("Loading image file for session edit: " . ($model->imageFile ? $model->imageFile->getUrl() : 'no file'), 'bbb');
            if ($previewImage->applyFile($model->imageFile)) {
                Yii::error("Applied image file for session edit: " . $model->imageFile->getUrl(), 'bbb');
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
            [['image_file_id', 'presentation_file_id', 'presentation_preview_file_id'], 'integer'],
            ['imageUpload', 'image', 'extensions' => 'png, jpg, jpeg', 'minWidth' => 200, 'minHeight' => 200],
            ['presentationUpload', 'file', 'extensions' => 'pdf', 'maxSize' => 40 * 1024 * 1024], // max. 40 MB
            [['publicJoin', 'joinCanStart', 'joinCanModerate', 'hasWaitingRoom', 'allowRecording', 'muteOnEntry', 'enabled', 'hidden'], 'boolean'],
            ['layout', 'required'],
            ['layout', 'in', 'range' => Layouts::values()],
            ['topics', 'safe'],
            [
                'visibility',
                'in',
                'range' => [
                    Content::VISIBILITY_PRIVATE,
                    Content::VISIBILITY_PUBLIC
                ]
            ],
        ];
    }

    public function load($data, $formName = null): bool
    {
        $result = parent::load($data, $formName);
        $iU = UploadedFile::getInstance($this, 'imageUpload');
        if ($iU) {
            Yii::error("Image uploaded: " . $iU->name, 'bbb');
            $this->imageUpload = $iU;
        }
        $pU = UploadedFile::getInstance($this, 'presentationUpload');
        if ($pU) {
            $this->presentationUpload = $pU;
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
            $session->content->visibility = $this->visibility;
            $session->content->hidden = $this->hidden;
        }
        Topic::attach($session->content, $this->topics);

        $session->id = $this->id;
        $session->name = $this->name;
        $session->title = $this->title;
        $session->description = $this->description;
        $session->moderator_pw = $this->moderator_pw;
        $session->attendee_pw = $this->attendee_pw;
        $session->public_join = $this->publicJoin;
        $session->join_can_start = $this->joinCanStart;
        $session->join_can_moderate = $this->joinCanModerate;
        $session->has_waitingroom = $this->hasWaitingRoom;
        $session->allow_recording = $this->allowRecording;
        $session->mute_on_entry = $this->muteOnEntry;
        $session->layout = $this->layout;
        $session->enabled = $this->enabled;
        $session->updated_at = time();

        $this->saveBlobRefs($session);

        if (!$session->save()) {
            Yii::error("Could not save session.", 'bbb');
            return false;
        }
        $this->id = $session->id;
        $this->record = $session;

        if (!$this->assignUsers($session)) {
            Yii::error("Could not assign users to session.", 'bbb');
            return false;
        }
        return true;
    }

    private function saveBlobRefs(Session $session): bool
    {
        // session image
        if ($this->imageUpload instanceof UploadedFile) {
            if (!$this->saveSessionImage($session)) {
                Yii::error("Could not save uploaded session image.", 'bbb');
            }
        }

        // Pdf presentation
        if ($this->presentationUpload instanceof UploadedFile) {
            if (!$this->savePresentation($session)) {
                Yii::error("Could not save uploaded presentation file.", 'bbb');
            }
        }
        return true;
    }

    private function saveSessionImage(Session $session): bool
    {
        $editImage = $session->image_file_id > 0;
        if ($editImage) {
            $humhubFile = $session->getImageFile();
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

        if (!$editImage) {
            $session->image_file_id = $humhubFile->id;
            $session->fileManager->attach($humhubFile->guid);
        }
        return true;
    }

    private function savePresentation(Session $session): bool
    {
        $path = $this->presentationUpload->tempName;
        $editPresentation = $session->presentation_file_id > 0;

        if ($editPresentation) {
            $humhubPresentationFile = $session->getPresentationFile();
        } else {
            $humhubPresentationFile = new File();
        }
        $humhubPresentationFile->file_name = $this->presentationUpload->baseName . '.' . $this->presentationUpload->extension;
        $humhubPresentationFile->mime_type = $this->presentationUpload->type;
        $humhubPresentationFile->size = $this->presentationUpload->size;

        if (!$humhubPresentationFile->save()) {
            // Wenn die File‐Metadaten nicht gespeichert werden können, abbrechen
            $this->addError('presentation', Yii::t('BbbModule.base', 'Could not save presentation reference to database.'));
            return false;
        }
        $humhubPresentationFile->setStoredFileContent(file_get_contents($path));

        if (!$editPresentation) {
            $session->presentation_file_id = $humhubPresentationFile->id;
            $session->fileManager->attach($humhubPresentationFile->guid);
        }

        return $this->savePresentationPreviewImage($session, $path);
    }

    private function savePresentationPreviewImage(Session $session, string $path): bool
    {
        // create preview image from first page of PDF
        $previewImageSuffix = '_preview.' . SessionForm::AUTO_IMAGE_FORMAT;
        $presentationPreviewImgPath = $path . $previewImageSuffix;
        if (
            Tools::pdfFirstPageToPng(
                $path,
                $presentationPreviewImgPath
            )
        ) {
            $editPresentationPreviewImage = $session->presentation_preview_file_id > 0;
            if ($editPresentationPreviewImage) {
                $humhubPresentationImageFile = $session->getPresentationPreviewImageFile();
            } else {
                $humhubPresentationImageFile = new File();
            }
            $humhubPresentationImageFile->file_name = $this->presentationUpload->baseName . $previewImageSuffix;
            $humhubPresentationImageFile->mime_type = "image/" . SessionForm::AUTO_IMAGE_FORMAT;
            $humhubPresentationImageFile->size = filesize($presentationPreviewImgPath);

            if (!$humhubPresentationImageFile->save()) {
                Yii::error("Could not save presentation preview image to database.", 'bbb');
            }
            $humhubPresentationImageFile->setStoredFileContent(
                file_get_contents($presentationPreviewImgPath),
                true
            );
            if (!$editPresentationPreviewImage) {
                $session->presentation_preview_file_id = $humhubPresentationImageFile->id;
                $session->fileManager->attach($humhubPresentationImageFile->guid);
            }
        }
        return true;
    }

    private function assignUsers(Session $session): bool
    {
        // Assign moderators
        if (!$this->moderateByPermissions) {
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
        if (!$this->joinByPermissions) {
            $attendeeDBUsers = User::find()
                ->where(['IN', 'guid', $this->attendeeRefs])
                ->andWhere(['NOT IN', 'guid', $this->moderatorRefs]) // keine Moderatoren als Teilnehmer
                ->all();
            foreach ($attendeeDBUsers as $user) {
                $this->addUser($user, $this->moderateByPermissions ? 'moderator' : 'attendee');
            }
        } else if ($this->record !== null) {
            SessionUser::deleteAll(['session_id' => $this->record->id, 'can_join' => true, 'role' => 'attendee']);
        }

        return true;
    }

    private function addUser(User $user, string $role): bool
    {
        $userRef = SessionUser::find()
            ->where(['session_id' => $this->record->id, 'user_id' => $user->id])
            ->one();
        if ($userRef === null) {
            $userRef = new SessionUser([
                'session_id' => $this->record->id,
                'user_id' => $user->id,
                'created_at' => time()
            ]);
        }
        $userRef->role = $role;
        $userRef->can_start = $role === 'moderator';
        $userRef->can_join = true;

        return $userRef->save();
    }
}
