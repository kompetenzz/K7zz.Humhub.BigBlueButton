<?php

namespace k7zz\humhub\bbb\controllers;

use k7zz\humhub\bbb\models\forms\SessionForm;
use k7zz\humhub\bbb\models\Session;
use k7zz\humhub\bbb\models\JoinInfo;
use Yii;
use yii\web\{ForbiddenHttpException, NotFoundHttpException, ServerErrorHttpException};

class SessionController extends BaseContentController
{

    public function actionIndex(?int $id = null, ?string $slug = null, ?string $containerId = null)
    {
        if ($id !== null) {
            $s = Session::find()->where(['id' => $id, 'contentcontainer_id' => $containerId])->one();
            if (!$s) {
                throw new NotFoundHttpException(Yii::t('BbbModule.base', 'Session with Id {id} not found.', ['id' => $id]));
            }
        } else if ($slug !== null) {
            $s = Session::find()->where(['name' => $slug, 'contentcontainer_id' => $containerId])->one();
            if (!$s) {
                throw new NotFoundHttpException(Yii::t('BbbModule.base', 'Session with slug {slug} not found.', ['slug' => $slug]));
            }
        }
        return $this->render('edit', ['model' => $s]);
    }

    public function actionEdit(?int $id = null, ?string $slug = null, ?string $containerId = null)
    {
        $session = $this->svc->get($id, $slug, $this->contentContainer ? $this->contentContainer->id : null)
            ?? throw new NotFoundHttpException();

        $form = SessionForm::edit($session);

        if ($form->load(Yii::$app->request->post()) && $form->save()) {
            $this->view->success(Yii::t('BbbModule.base', 'Session saved.'));
            return $this->redirect(['sessions/', 'highlight' => $form->id, 'containerId' => $form->containerId]);
        }
        return $this->render('edit', ['model' => $form]);
    }

    public function actionStart(?int $id = null, ?string $slug = null, bool $embed = true, bool $void = false)
    {
        if ($id === null && $slug === null) {
            throw new NotFoundHttpException();
        }
        $session = $this->svc->get($id, $slug, $this->contentContainer ? $this->contentContainer->id : null)
            ?? throw new NotFoundHttpException();
        if (!$this->svc->isRunning($session->uuid)) {
            if (!$session->canStart()) {
                throw new ForbiddenHttpException();
            }
            $joinUrl = $this->svc->start($session);
            if (!$joinUrl) {
                throw new ServerErrorHttpException(
                    Yii::t('BbbModule.base', 'Could not start session {title}.', ['title' => $session->title])
                );
            }
        }

        if ($void)
            return Yii::$app->response->redirect(Yii::$app->request->referrer);
        else
            return $embed ?
                Yii::$app->response->redirect("/bbb/session/embed?id={$session->id}") :
                Yii::$app->response->redirect("/bbb/session/join?id={$session->id}");
    }

    public function actionQuit(?int $id = null, ?string $slug = null)
    {
        $session = $this->svc->get($id, $slug)
            ?? throw new NotFoundHttpException();

        // return $this->render('quit', compact('session'));
        return Yii::$app->response->redirect(["bbb/sessions", "highlight" => $session->id]);

    }

    private function prepareJoin(?int $id = null, ?string $slug = null): JoinInfo
    {
        if ($id === null && $slug === null) {
            throw new NotFoundHttpException();
        }
        $session = $this->svc->get($id, $slug, $this->contentContainer ? $this->contentContainer->id : null)
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
    public function actionJoin(?int $id = null, ?string $slug = null)
    {
        $joinInfo = $this->prepareJoin($id, $slug);
        return Yii::$app->response->redirect($joinInfo->url, 303, true);
    }

    /* ------------- iframe embed --------------------------------------- */
    public function actionEmbed(?int $id = null, ?string $slug = null)
    {
        $joinInfo = $this->prepareJoin($id, $slug);
        return $this->render('embed', compact('joinInfo'));
    }

}
