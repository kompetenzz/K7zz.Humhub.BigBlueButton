<?php
namespace k7zz\humhub\bbb\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use k7zz\humhub\bbb\models\Session;
use k7zz\humhub\bbb\services\SessionService; // dein Wrapper
use yii\helpers\Url;
use yii\web\Response;

class PublicController extends Controller
{
    /**
     * The session service instance for BBB logic.
     * @var SessionService|null
     */
    protected ?SessionService $svc = null;

    /**
     * Initializes the controller and the session service.
     */
    public function init()
    {
        parent::init();
        $this->svc = Yii::createObject(SessionService::class);
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'actions' => ['join', 'download'], 'roles' => ['?', '@']],
                ],
            ],
        ];
    }

    public function actionJoin(string $token, string $name = null)
    {
        $session = Session::find()->where(['public_token' => $token])->one();
        $msg = '';
        if (!$session) {
            $msg = Yii::t('BbbModule.base', 'No such session.');
        } else if (!$session->public_join) {
            $msg = Yii::t('BbbModule.base', 'Session not public.');
        } else if (!$this->svc->isRunning($session->uuid)) {
            $msg = Yii::t('BbbModule.base', 'Session not running.');
            $reload = true;
        }

        if ($msg || !$name || mb_strlen(trim($name)) < 2) {
            return $this->render('join', [
                'title' => $session->title,
                'token' => $token,
                'msg' => $msg,
                'reload' => $reload ?? false,
            ]);
        }

        $displayName = mb_substr(trim($name), 0, 60);

        $joinUrl = $this->svc->anonymousJoinUrl($session, $displayName);

        return $this->redirect($joinUrl);
    }

    public function actionDownload(string $token, string $type = "presentation"): Response
    {
        $session = Session::find()->where(['public_token' => $token])->one();
        if (!$session)
            throw new NotFoundHttpException(Yii::t('BbbModule.base', 'No such session.'));

        if ($type === "presentation" && $session->presentation_file_id) {
            $file = $session->getPresentationFile();
            if ($file) {
                return Yii::$app->response->sendFile($file->getStore()->get(), $file->file_name, ['inline' => false]);
            }
        }
    }
}