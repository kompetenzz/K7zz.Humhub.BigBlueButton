<?php

namespace k7zz\humhub\bbb\controllers;

use k7zz\humhub\bbb\models\forms\SessionForm;
use k7zz\humhub\bbb\models\Session;
use k7zz\humhub\bbb\models\JoinInfo;
use Yii;
use yii\helpers\Url;
use yii\web\{ForbiddenHttpException, NotFoundHttpException, ServerErrorHttpException};

class SessionController extends BaseContentController
{

    public function actionIndex(?int $id = null)
    {
        if ($id === null) {
            throw new NotFoundHttpException();
        }
        return $this->actionEdit($id);
    }

    public function actionCreate()
    {
        $form = SessionForm::create($this->contentContainer);

        if ($form->load(Yii::$app->request->post()) && $form->save()) {
            $this->view->success(Yii::t('BbbModule.base', 'Session created.'));
            return $this->redirect([$this->getUrl('/bbb/sessions'), 'highlight' => $form->id]);
        }
        return $this->render('edit', ['model' => $form]);
    }

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
            }
        }
        if ($void)
            return Yii::$app->response->redirect(Yii::$app->request->referrer);

        $actionName = $embed ? "embed" : "join";
        return $this->redirect($this->getUrl("/bbb/session/{$actionName}/{$session->name}"));
    }

    public function actionQuit(?int $id = null)
    {
        $session = $this->svc->get($id)
            ?? throw new NotFoundHttpException(Yii::t('BbbModule.base', 'Session with Id {id} not found.', ['id' => $id]));
        return $this->redirect($this->getUrl("/bbb/sessions?highlight={$session->id}"));

    }

    public function actionExit()
    {
        return $this->render('exit');
    }

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

    public function actionDelete(int $id)
    {
        if ($this->svc->delete($id, $this->contentContainer)) {
            $this->view->success(Yii::t('BbbModule.base', 'Session deleted.'));

        } else {
            $this->view->error(Yii::t('BbbModule.base', 'Could not delete session.'));
        }
        return $this->redirect($this->getUrl("/bbb/sessions"));
    }

    public function actionRecordings(?int $id = null): array
    {
        $session = $this->svc->get($id, $this->contentContainer)
            ?? throw new NotFoundHttpException(Yii::t('BbbModule.base', 'Session with Id {id} not found.', ['id' => $id]));

        $result = [];
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (!$session->canJoin()) {
            return $result;
        }
        $recordings = $this->svc->getRecordings($id, $this->contentContainer);
        foreach ($recordings as $r) {
            $start = intval($r->getStartTime() / 1000);
            $end = intval($r->getEndTime() / 1000);
            $url = $r->getPlaybackUrl() ?? null;

            if (!$url)
                continue;

            $result[] = [
                'id' => $r->getRecordId(),
                'date' => Yii::$app->formatter->asDate($start),
                'time' => Yii::$app->formatter->asTime($start, "H:mm"),
                'duration' => gmdate("H:i:s", $end - $start),
                'url' => $url,
                'type' => $r->getPlaybackType(),
                'name' => $r->getName(),
                'isPublished' => $r->isPublished(),
                'state' => Yii::t('BbbModule.base', $r->getState()),
            ];
        }
        return $result;
    }
    public function actionRecordingsCount(?int $id = null): int
    {
        $session = $this->svc->get($id, $this->contentContainer)
            ?? throw new NotFoundHttpException(Yii::t('BbbModule.base', 'Session with Id {id} not found.', ['id' => $id]));

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (!$session->canJoin()) {
            return 0;
        }
        $recordings = array_filter(
            $this->svc->getRecordings($id, $this->contentContainer),
            fn($r) => $session->canAdminister() || !empty($r->getPlaybackUrl())
        );
        return count($recordings);
    }

    /** 
     * Toggle publish state of a recording 
     * */
    public function actionPublishRecording(string $recordId, bool $publish = false): bool
    {
        Yii::error($recordId);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $this->svc->publishRecording($recordId, $publish);
    }
}
