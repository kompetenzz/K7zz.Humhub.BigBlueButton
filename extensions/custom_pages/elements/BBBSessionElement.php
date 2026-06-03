<?php

namespace k7zz\humhub\bbb\extensions\custom_pages\elements;

use humhub\modules\custom_pages\modules\template\elements\BaseContentRecordElement;
use humhub\modules\custom_pages\modules\template\elements\BaseElementVariable;
use humhub\modules\space\models\Space;
use humhub\widgets\form\ActiveForm;
use k7zz\humhub\bbb\assets\BBBAssets;
use k7zz\humhub\bbb\models\Session;
use k7zz\humhub\bbb\services\SessionService;
use Yii;

/**
 * Custom Pages element for a single BBB session.
 *
 * @property-read Session|null $record
 */
class BBBSessionElement extends BaseContentRecordElement implements \Stringable
{
    protected const RECORD_CLASS = Session::class;

    public function getLabel(): string
    {
        return Yii::t('BbbModule.base', 'BBB Session');
    }

    public function attributeLabels()
    {
        return [
            'contentId' => Yii::t('BbbModule.base', 'BBB Session'),
        ];
    }

    public function attributeHints()
    {
        return [];
    }

    public function renderEditForm(ActiveForm $form): string
    {
        $sessions = Session::find()
            ->joinWith('content')
            ->where(['bbb_session.deleted_at' => null])
            ->orderBy(['bbb_session.title' => SORT_ASC])
            ->all();

        $global = [];
        $spaces = [];
        $users  = [];

        foreach ($sessions as $session) {
            $container = $session->content->container;
            $contentId = $session->content->id;
            if ($container === null) {
                $global[$contentId] = $session->title;
            } elseif ($container instanceof Space) {
                $spaces[$container->getDisplayName()][$contentId] = $session->title;
            } else {
                $users[$container->getDisplayName()][$contentId] = $session->title;
            }
        }

        ksort($spaces);
        ksort($users);

        $options = ['' => '— ' . Yii::t('BbbModule.base', 'Please select') . ' —'];
        if (!empty($global)) {
            $options[Yii::t('BbbModule.base', 'Global')] = $global;
        }
        if (!empty($spaces)) {
            foreach ($spaces as $spaceName => $entries) {
                $options['Space: ' . $spaceName] = $entries;
            }
        }
        if (!empty($users)) {
            foreach ($users as $userName => $entries) {
                $options['User: ' . $userName] = $entries;
            }
        }

        return $form->field($this, 'contentId')->dropDownList($options);
    }

    public function __toString(): string
    {
        $session = $this->getRecord();
        if (!$session) {
            return '';
        }
        return self::renderSession($session);
    }

    public static function renderSession(Session $session): string
    {
        [$running, $bundle] = self::prepareRender($session);
        return Yii::$app->view->renderFile(__DIR__ . '/views/_session.php', [
            'session' => $session,
            'running' => $running,
            'bundle'  => $bundle,
        ]);
    }

    public static function renderSessionRow(Session $session, bool $isLast = false): string
    {
        [$running, $bundle] = self::prepareRender($session);
        return Yii::$app->view->renderFile(__DIR__ . '/views/_session_row.php', [
            'session' => $session,
            'running' => $running,
            'bundle'  => $bundle,
            'isLast'  => $isLast,
        ]);
    }

    private static function prepareRender(Session $session): array
    {
        try {
            $running = (new SessionService())->isRunning($session->uuid);
        } catch (\Throwable $e) {
            Yii::error($e, 'bbb');
            $running = false;
        }
        return [$running, BBBAssets::register(view: Yii::$app->view)];
    }

    public function getTemplateVariable(): BaseElementVariable
    {
        return BBBSessionElementVariable::instance($this)
            ->setRecord($this->getRecord());
    }
}
