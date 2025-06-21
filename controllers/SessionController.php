<?php

namespace k7zz\humhub\bbb\controllers;

use k7zz\humhub\bbb\models\forms\SessionForm;
use k7zz\humhub\bbb\models\Session;
use k7zz\humhub\bbb\models\JoinInfo;
use Yii;
use yii\web\{ForbiddenHttpException, NotFoundHttpException, ServerErrorHttpException};

class SessionController extends BaseContentController
{

    public function actionIndex(?int $id = null, ?string $containerId = null)
    {
        if ($id !== null) {
            $s = Session::find()->where(['id' => $id, 'contentcontainer_id' => $containerId])->one();
            if (!$s) {
                throw new NotFoundHttpException(Yii::t('BbbModule.base', 'Session with Id {id} not found.', ['id' => $id]));
            }
        }
        return $this->render('edit', ['model' => $s]);
    }

    public function actionCreate(?int $containerId = null)
    {
        $form = SessionForm::create($containerId);

        if ($form->load(Yii::$app->request->post()) && $form->save()) {
            $this->view->success(Yii::t('BbbModule.base', 'Session created.'));
            $url = $containerId && $this->contentContainer
                ? $this->contentContainer->createUrl('/bbb/sessions') : '/bbb/sessions';
            return $this->redirect([$url, 'highlight' => $form->id]);
        }
        return $this->render('edit', ['model' => $form]);
    }

    public function actionEdit(?int $id = null, ?string $containerId = null)
    {
        $session = $this->svc->get($id, $this->contentContainer ? $this->contentContainer->id : null)
            ?? throw new NotFoundHttpException();

        $form = SessionForm::edit($session);

        if ($form->load(Yii::$app->request->post()) && $form->save()) {
            $this->view->success(Yii::t('BbbModule.base', 'Session saved.'));
            return $this->redirect(['sessions/', 'highlight' => $form->id, 'containerId' => $form->containerId]);
        }
        return $this->render('edit', ['model' => $form]);
    }

    public function actionStart(?int $id = null, bool $embed = true, bool $void = false, ?string $space = null)
    {
        if ($id === null) {
            throw new NotFoundHttpException();
        }
        $session = $this->svc->get($id, $this->contentContainer ? $this->contentContainer->id : null)
            ?? throw new NotFoundHttpException();
        if (!$this->svc->isRunning($session->uuid)) {
            if (!$session->canStart()) {
                throw new ForbiddenHttpException();
            }
            $joinUrl = $this->svc->start($session, $this->contentContainer);
            if (!$joinUrl) {
                throw new ServerErrorHttpException(
                    Yii::t('BbbModule.base', 'Could not start session "{title}".', ['title' => $session->title])
                );
            }
        }
        if ($void)
            return Yii::$app->response->redirect(Yii::$app->request->referrer);

        $actionName = $embed ? "embed" : "join";
        return $this->redirect("/bbb/session/{$actionName}/{$session->name}");
    }

    public function actionQuit(?int $id = null)
    {
        $session = $this->svc->get($id)
            ?? throw new NotFoundHttpException();
        return $this->redirect("/bbb/sessions?highlight={$session->id}", $session->contentcontainer_id);

        // return $this->render('quit', compact('session'));
        // return Yii::$app->response->redirect(["bbb/sessions", "highlight" => $session->id]);
    }

    private function prepareJoin(?int $id = null): JoinInfo
    {
        if ($id === null) {
            throw new NotFoundHttpException();
        }
        $session = $this->svc->get($id, $this->contentContainer ? $this->contentContainer->id : null)
            ?? throw new NotFoundHttpException();

        if (!$session->canJoin()) {
            throw new ForbiddenHttpException();
        }
        $result = new JoinInfo();
        $result->url = $this->svc->joinUrl(
            $session,
            $session->isModerator()
        );
        $result->title = Yii::t('BbbModule.base', 'Live session') . ': ' . $session->title;
        return $result;
    }

    /* ------------- Join in bbb (use with target, or window.open) --------------------------------------- */
    public function actionJoin(?int $id = null)
    {
        $joinInfo = $this->prepareJoin($id);
        return Yii::$app->response->redirect($joinInfo->url, 303, true);
    }

    /* ------------- iframe embed --------------------------------------- */
    public function actionEmbed(?int $id = null)
    {
        $joinInfo = $this->prepareJoin($id);
        return $this->render('embed', compact('joinInfo'));
    }

}
