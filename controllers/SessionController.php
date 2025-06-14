<?php

namespace humhub\modules\bbb\controllers;

use humhub\modules\bbb\models\forms\SessionForm;
use humhub\modules\bbb\models\Session;
use humhub\modules\bbb\models\JoinInfo;
use Yii;
use yii\web\{ForbiddenHttpException, NotFoundHttpException, ServerErrorHttpException};

class SessionController extends \humhub\modules\bbb\controllers\BaseContentController
{

    public function actionIndex(?int $id = null, ?string $slug = null, ?string $containerId = null)
    {
        if ($id !== null) {
            $s = \humhub\modules\bbb\models\Session::find()->where(['id' => $id, 'contentcontainer_id' => $containerId])->one();
            if (!$s) {
                throw new NotFoundHttpException(Yii::t('BbbModule.base', 'Session with Id {id} not found.', ['id' => $id]));
            }
        } else if ($slug !== null) {
            $s = \humhub\modules\bbb\models\Session::find()->where(['name' => $slug, 'contentcontainer_id' => $containerId])->one();
            if (!$s) {
                throw new NotFoundHttpException(Yii::t('BbbModule.base', 'Session with slug {slug} not found.', ['slug' => $slug]));
            }
        }
        return $this->render('edit', ['model' => $s]);
    }

    public function actionEdit(?int $id = null, ?string $slug = null, ?string $containerId = null)
    {
        $form = SessionForm::edit($id, $slug, $containerId ?? 0);

        if ($form->load(Yii::$app->request->post()) && $form->save()) {
            $this->view->success(Yii::t('BbbModule.base', 'Session saved.'));
            return $this->redirect(['sessions/', 'highlight' => $form->id, 'containerId' => $form->containerId]);
        }
        return $this->render('edit', ['model' => $form]);
    }

    public function actionStart(?int $id = null, ?string $slug = null, bool $embed = true, bool $void = false)
    {
        if ($id === 0) {
            throw new NotFoundHttpException();
        }
        $session = $this->svc->get($id, $this->contentContainer ? $this->contentContainer->id : null)
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

    private function prepareJoin(?int $id = null, ?string $slug = null): JoinInfo
    {
        if ($id === 0) {
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
    public function actionJoin(?int $id = null, ?string $slug = null)
    {
        $joinInfo = $this->prepareJoin($id);
        return Yii::$app->response->redirect($joinInfo->url, 303, true);
    }

    /* ------------- iframe embed --------------------------------------- */
    public function actionEmbed(?int $id = null, ?string $slug = null)
    {
        $joinInfo = $this->prepareJoin($id);
        return $this->render('embed', compact('joinInfo'));
    }

}
