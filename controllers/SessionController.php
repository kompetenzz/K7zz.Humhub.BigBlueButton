<?php

namespace k7zz\humhub\bbb\controllers;

use humhub\components\access\ControllerAccess;

use k7zz\humhub\bbb\models\forms\SessionForm;
use k7zz\humhub\bbb\models\Session;
use k7zz\humhub\bbb\models\Recording;
use k7zz\humhub\bbb\models\JoinInfo;

use Yii;
use yii\helpers\Url;
use yii\filters\VerbFilter;
use yii\web\{ForbiddenHttpException, NotFoundHttpException, ServerErrorHttpException};

/**
 * Controller for handling BBB session actions in HumHub.
 *
 * Provides endpoints for:
 * - Creating, editing, and deleting sessions
 * - Starting and joining meetings (including embed/iframe)
 * - Listing and counting recordings
 * - Publishing/unpublishing recordings
 *
 * All actions are permission-checked and use the SessionService for business logic.
 */
class SessionController extends BaseContentController
{
    /**
     * Redirects to the edit action for a given session ID.
     * @param int|null $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionIndex(?int $id = null)
    {
        if ($id === null) {
            throw new NotFoundHttpException();
        }
        return $this->actionEdit($id);
    }

    /**
     * Creates a new BBB session via form.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $form = SessionForm::create($this->contentContainer);

        if ($form->load(Yii::$app->request->post()) && $form->save()) {
            $this->view->success(Yii::t('BbbModule.base', 'Session created.'));
            return $this->redirect([$this->getUrl('/bbb/sessions'), 'highlight' => $form->id]);
        }
        return $this->render('edit', ['model' => $form]);
    }

    /**
     * Edits an existing BBB session.
     * @param int|null $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionEdit(?int $id = null)
    {
        if ($id === null) {
            throw new NotFoundHttpException();
        }
        $session = $this->svc->get($id, $this->contentContainer)
            ?? throw new NotFoundHttpException(Yii::t('BbbModule.base', 'Session with Id {id} not found.', ['id' => $id]));

        $form = SessionForm::edit($session);

        if ($form->load(Yii::$app->request->post()) && $form->save()) {
            $this->view->success(Yii::t('BbbModule.base', 'Session saved.'));
            return $this->redirect([$this->getUrl('sessions/'), 'highlight' => $form->id]);
        }
        return $this->render('edit', ['model' => $form]);
    }

    /**
     * Starts a BBB session (meeting) or joins if already running.
     * @param int|null $id
     * @param bool $embed
     * @param bool $void
     * @param string|null $space
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionStart(?int $id = null, bool $embed = true, bool $void = false, ?string $space = null)
    {
        if ($id === null) {
            throw new NotFoundHttpException();
        }
        $session = $this->svc->get($id, $this->contentContainer)
            ?? throw new NotFoundHttpException(Yii::t('BbbModule.base', 'Session with Id {id} not found.', ['id' => $id]));
        if (!$this->svc->isRunning($session->uuid)) {
            if (!$session->canStart()) {
                Yii::$app->getSession()->setFlash('access-denied', Yii::t('BbbModule.base', 'You are not allowed to start session "{title}".', ['title' => $session->title]));
            } else {
                $joinUrl = $this->svc->start($session, $this->contentContainer);
                if (!$joinUrl) {
                    Yii::$app->getSession()->setFlash('access-denied', Yii::t('BbbModule.base', 'Could not start session "{title}".', ['title' => $session->title]));
                }
                return Yii::$app->response->redirect($joinUrl, 303, true);
            }
        }
        if ($void)
            return Yii::$app->response->redirect(Yii::$app->request->referrer);

        $actionName = $embed ? "embed" : "join";
        return $this->redirect($this->getUrl("/bbb/session/{$actionName}/{$session->name}"));
    }

    /**
     * Quits a session and redirects to the session list.
     * @param int|null $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionQuit(?int $id = null)
    {
        $session = $this->svc->get($id)
            ?? throw new NotFoundHttpException(Yii::t('BbbModule.base', 'Session with Id {id} not found.', ['id' => $id]));
        return $this->redirect($this->getUrl("/bbb/sessions?highlight={$session->id}"));

    }

    /**
     * Renders the exit view for a session.
     * @return string
     */
    public function actionExit()
    {
        return $this->render('exit');
    }

    /**
     * Prepares join information for a session, including join URL and title.
     * @param int|null $id
     * @return JoinInfo
     * @throws NotFoundHttpException|ForbiddenHttpException
     */
    private function prepareJoin(?int $id = null): JoinInfo
    {
        if ($id === null) {
            throw new NotFoundHttpException();
        }
        $session = $this->svc->get($id, $this->contentContainer)
            ?? throw new NotFoundHttpException(Yii::t('BbbModule.base', 'Session with Id {id} not found.', ['id' => $id]));

        if (!$session->canJoin()) {
            throw new ForbiddenHttpException();
        }
        $result = new JoinInfo();
        $result->url = $this->svc->joinUrl(
            $session,
            $session->isModerator() || $session->join_can_moderate,
        );
        $result->title = Yii::t('BbbModule.base', 'Live session') . ': ' . $session->title;
        return $result;
    }

    /**
     * Joins a BBB session (redirects to join URL).
     * @param int|null $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException|ForbiddenHttpException
     */
    public function actionJoin(?int $id = null)
    {
        $joinInfo = $this->prepareJoin($id);
        return Yii::$app->response->redirect($joinInfo->url, 303, true);
    }

    /**
     * Embeds a BBB session in an iframe.
     * @param int|null $id
     * @return string
     * @throws NotFoundHttpException|ForbiddenHttpException
     */
    public function actionEmbed(?int $id = null)
    {
        $joinInfo = $this->prepareJoin($id);
        return $this->render('embed', compact('joinInfo'));
    }

    /**
     * Deletes a BBB session.
     * @param int $id
     * @return \yii\web\Response
     */
    public function actionDelete(int $id)
    {
        if ($this->svc->delete($id, $this->contentContainer)) {
            $this->view->success(Yii::t('BbbModule.base', 'Session deleted.'));

        } else {
            $this->view->error(Yii::t('BbbModule.base', 'Could not delete session.'));
        }
        return $this->redirect($this->getUrl("/bbb/sessions"));
    }

    /**
     * Returns a list of recordings for a session as JSON.
     * @param int|null $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionRecordings(?int $id = null): string
    {
        if ($id === null) {
            throw new NotFoundHttpException();
        }
        $session = $this->svc->get($id, $this->contentContainer)
            ?? throw new NotFoundHttpException(Yii::t('BbbModule.base', 'Session with Id {id} not found.', ['id' => $id]));

        $result = [];

        if (!$session->canJoin()) {
            return "";
        }

        $recordings = $this->svc->getRecordings($id, $this->contentContainer);

        foreach ($recordings as $r) {
            $result[] = new Recording($r);
        }

        return $this->renderAjax('_recordings', [
            'recordings' => $result,
            'canAdminister' => $session->canAdminister(),
        ]);
    }

    /**
     * Returns the number of recordings for a session as JSON.
     * @param int|null $id
     * @return Yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionRecordingsCount(?int $id = null)
    {
        if ($id === null) {
            throw new NotFoundHttpException();
        }
        $session = $this->svc->get($id, $this->contentContainer)
            ?? throw new NotFoundHttpException(Yii::t('BbbModule.base', 'Session with Id {id} not found.', ['id' => $id]));

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (!$session->canJoin()) {
            return 0;
        }
        $recordings = array_filter(
            $this->svc->getRecordings($id, $this->contentContainer),
            fn($r) => $session->canAdminister()// || !empty($r->getPlaybackUrl())
        );
        return $this->asJson(count($recordings));
    }

    /**
     * Toggles the publish state of a recording.
     * @param string $recordId
     * @param bool $publish
     * @return Yii\web\Response
     */
    public function actionPublishRecording(string $recordId, bool $publish = false)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $this->asJson($this->svc->publishRecording($recordId, $publish));
    }
}
