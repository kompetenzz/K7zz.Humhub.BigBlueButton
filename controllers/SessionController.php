<?php

namespace k7zz\humhub\bbb\controllers;

use humhub\components\access\ControllerAccess;
use humhub\modules\user\models\User;

use k7zz\humhub\bbb\models\forms\SessionForm;
use k7zz\humhub\bbb\models\SessionMeeting;
use k7zz\humhub\bbb\models\SessionChatReaction;
use k7zz\humhub\bbb\models\SessionMeetingChat;
use k7zz\humhub\bbb\models\SessionUser;
use k7zz\humhub\bbb\notifications\ChatMsgReaction;
use k7zz\humhub\bbb\notifications\ChatMsgReceived;
use k7zz\humhub\bbb\notifications\SessionStarted;
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
     * Session landing page — shows session details and join button without auto-redirecting.
     * @param int|null $id
     * @return string
     * @throws NotFoundHttpException|ForbiddenHttpException
     */
    public function actionIndex(?int $id = null)
    {
        if ($id === null) {
            throw new NotFoundHttpException();
        }
        $session = $this->svc->get($id, $this->contentContainer)
            ?? throw new NotFoundHttpException(Yii::t('BbbModule.base', 'Session with Id {id} not found.', ['id' => $id]));

        if (!$session->canJoin()) {
            throw new ForbiddenHttpException();
        }

        $running     = $this->svc->isRunning($session->uuid);
        $chatEnabled = (bool) (Yii::$app->getModule('bbb')->settings->get('integrateBbbChat') ?? false)
            && (bool) $session->integrate_bbb_chat;
        $routeBase = fn($route, $params = []) => $this->contentContainer
            ? $this->contentContainer->createUrl($route, $params)
            : Url::to(array_merge([$route], $params));

        $chatMessages = $chatEnabled
            ? SessionMeetingChat::findAllForSession($session->id)->all()
            : [];

        return $this->render('index', [
            'session'          => $session,
            'running'          => $running,
            'chatEnabled'      => $chatEnabled,
            'canStart'         => $session->canStart(),
            'startUrl'         => $this->getUrl("/bbb/session/start/{$session->name}") . '?embed=0',
            'joinUrl'          => Url::to($routeBase('/bbb/session/join', ['id' => $session->id]), true),
            'isRunningUrl'     => $this->contentContainer
                ? $this->contentContainer->createUrl('/bbb/session/is-running', ['id' => $session->id])
                : Url::to(['/bbb/session/is-running', 'id' => $session->id]),
            'preMeetingChats'  => $chatMessages,
            'recordingsEnabled' => $session->canJoin(),
        ]);
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
                if ($session->notify_on_start) {
                    $this->notifySessionStarted($session);
                }
                return Yii::$app->response->redirect($joinUrl, 303, true);
            }
        }
        if ($void)
            return Yii::$app->response->redirect(Yii::$app->request->referrer);

        $actionName = $embed ? "embed" : "join";
        return $this->redirect($this->getUrl(url: "/bbb/session/{$actionName}/{$session->name}"));
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
     * Joins a BBB session. If not running, shows a waiting page with session overview.
     * @param int|null $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException|ForbiddenHttpException
     */
    public function actionJoin(?int $id = null)
    {
        if ($id === null) {
            throw new NotFoundHttpException();
        }
        $session = $this->svc->get($id, $this->contentContainer)
            ?? throw new NotFoundHttpException(Yii::t('BbbModule.base', 'Session with Id {id} not found.', ['id' => $id]));

        if (!$session->canJoin()) {
            throw new ForbiddenHttpException();
        }

        $joinUrl = $this->svc->joinUrl(
            $session,
            $session->isModerator() || $session->join_can_moderate,
        );

        if (!$this->svc->isRunning($session->uuid)) {
            $chatMessages = $session->integrate_bbb_chat
                ? SessionMeetingChat::findAllForSession($session->id)->all()
                : [];

            return $this->render('join', [
                'session'         => $session,
                'canStart'        => $session->canStart(),
                'startUrl'        => $this->getUrl("/bbb/session/start/{$session->name}") . '?embed=0',
                'joinUrl'         => $joinUrl,
                'running'         => false,
                'isRunningUrl'    => $this->contentContainer
                    ? $this->contentContainer->createUrl('/bbb/session/is-running', ['id' => $session->id])
                    : Url::to(['/bbb/session/is-running', 'id' => $session->id]),
                'preMeetingChats' => $chatMessages,
            ]);
        }

        return Yii::$app->response->redirect($joinUrl, 303, true);
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
     * Returns JSON whether a session is currently running.
     * Used by the join-waiting page to poll without a full reload.
     * @param int|null $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException|ForbiddenHttpException
     */
    public function actionIsRunning(?int $id = null)
    {
        if ($id === null) {
            throw new NotFoundHttpException();
        }
        $session = $this->svc->get($id, $this->contentContainer)
            ?? throw new NotFoundHttpException(Yii::t('BbbModule.base', 'Session with Id {id} not found.', ['id' => $id]));

        if (!$session->canJoin()) {
            throw new ForbiddenHttpException();
        }

        $running = $this->svc->isRunning($session->uuid);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $response = ['running' => $running];
        if ($session->canStart() && Yii::$app->cache->get('bbb:hook_failed:' . $session->id) !== false) {
            $response['hookFailed'] = true;
        }
        return $this->asJson($response);
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
        $recordings = $this->svc->getRecordings($id, $this->contentContainer);
        return $this->asJson(count($recordings));
    }

    /**
     * Toggles the publish state of a single recording format.
     * POST params: recordId, formatType, publish ('true'/'false')
     * @return \yii\web\Response
     */
    public function actionPublishRecording()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $recordId = Yii::$app->request->post('recordId');
        $formatType = Yii::$app->request->post('formatType');
        $publish = Yii::$app->request->post('publish') === 'true';

        if (!$recordId || !$formatType) {
            Yii::$app->response->statusCode = 400;
            return $this->asJson(['error' => 'Missing recordId or formatType']);
        }

        $ok = $this->svc->publishRecordingFormat($recordId, $formatType, $publish);
        return $this->asJson(['status' => $ok ? 200 : 500]);
    }

    /**
     * POST: send a chat message for a session.
     * Saves the message and notifies all moderators.
     */
    public function actionSendChat(int $id): \yii\web\Response
    {
        $session = $this->svc->get($id, $this->contentContainer)
            ?? throw new NotFoundHttpException();

        if (!$session->canJoin()) {
            throw new ForbiddenHttpException();
        }

        $message = trim(Yii::$app->request->post('message', ''));
        if ($message === '') {
            return $this->asJson(['status' => 400, 'error' => Yii::t('BbbModule.base', 'Message cannot be empty.')]);
        }

        $user = Yii::$app->user->identity;

        $running = $this->svc->isRunning($session->uuid);
        $meeting = $running
            ? SessionMeeting::find()
                ->where(['session_id' => $session->id, 'ended_at' => null])
                ->orderBy(['started_at' => SORT_DESC])
                ->one()
            : null;

        $chat = new SessionMeetingChat([
            'session_id'         => $session->id,
            'session_meeting_id' => $meeting?->id,
            'user_id'            => $user->id,
            'sender_name'        => $user->displayName,
            'message'            => $message,
            'source'             => SessionMeetingChat::SOURCE_HUMHUB,
            'sent_at'            => null,
        ]);

        if (!$chat->save()) {
            return $this->asJson(['status' => 500, 'error' => Yii::t('BbbModule.base', 'Could not save message.')]);
        }

        if ($meeting !== null) {
            if ($this->svc->sendChatToMeeting($session, $message, $user->displayName)) {
                $chat->sent_at = time();
                $chat->save();
            }
            // If BBB injection fails: message stays (sent_at=null) and will be visible in HumHub chat
        }

        ChatMsgReceived::notifyModerators($chat, $user);

        return $this->asJson(['status' => 200]);
    }

    /**
     * POST: toggle an emoji reaction on a chat message.
     * Params: chatId, emoji. Notifies the message author on add.
     */
    public function actionChatReact(int $id): \yii\web\Response
    {
        $session = $this->svc->get($id, $this->contentContainer)
            ?? throw new NotFoundHttpException();

        if (!$session->canJoin()) {
            throw new ForbiddenHttpException();
        }

        $chatId = (int) Yii::$app->request->post('chatId');
        $emoji  = (string) Yii::$app->request->post('emoji');

        $chat = SessionMeetingChat::findOne(['id' => $chatId, 'session_id' => $session->id]);
        if ($chat === null || $chat->source === SessionMeetingChat::SOURCE_SYSTEM) {
            return $this->asJson(['status' => 404]);
        }
        if (!in_array($emoji, SessionChatReaction::ALLOWED_EMOJIS, true)) {
            return $this->asJson(['status' => 400]);
        }

        $reaction = SessionChatReaction::toggle($chat->id, Yii::$app->user->id, $emoji);
        if ($reaction !== null) {
            ChatMsgReaction::notifyAuthor($reaction);
        }

        return $this->asJson(['status' => 200]);
    }

    /**
     * POST: edit an own chat message (HumHub-sourced only).
     * Params: chatId, message.
     */
    public function actionChatEdit(int $id): \yii\web\Response
    {
        $session = $this->svc->get($id, $this->contentContainer)
            ?? throw new NotFoundHttpException();

        if (!$session->canJoin()) {
            throw new ForbiddenHttpException();
        }

        $chatId  = (int) Yii::$app->request->post('chatId');
        $message = trim(Yii::$app->request->post('message', ''));

        $chat = SessionMeetingChat::findOne(['id' => $chatId, 'session_id' => $session->id]);
        if ($chat === null) {
            return $this->asJson(['status' => 404]);
        }
        if ($chat->source !== SessionMeetingChat::SOURCE_HUMHUB || $chat->user_id !== (int) Yii::$app->user->id) {
            throw new ForbiddenHttpException();
        }
        if ($message === '') {
            return $this->asJson(['status' => 400, 'error' => Yii::t('BbbModule.base', 'Message cannot be empty.')]);
        }

        if ($chat->message !== $message) {
            $chat->message   = $message;
            $chat->edited_at = time();
            if (!$chat->save()) {
                return $this->asJson(['status' => 500]);
            }
        }

        return $this->asJson(['status' => 200]);
    }

    /**
     * POST: delete a chat message. Allowed for the author and session moderators.
     * Params: chatId.
     */
    public function actionChatDelete(int $id): \yii\web\Response
    {
        $session = $this->svc->get($id, $this->contentContainer)
            ?? throw new NotFoundHttpException();

        if (!$session->canJoin()) {
            throw new ForbiddenHttpException();
        }

        $chatId = (int) Yii::$app->request->post('chatId');
        $chat = SessionMeetingChat::findOne(['id' => $chatId, 'session_id' => $session->id]);
        if ($chat === null || $chat->source === SessionMeetingChat::SOURCE_SYSTEM) {
            return $this->asJson(['status' => 404]);
        }

        $isOwn = $chat->user_id !== null && $chat->user_id === (int) Yii::$app->user->id;
        if (!$isOwn && !$session->isModerator() && !$session->canAdminister()) {
            throw new ForbiddenHttpException();
        }

        $chat->delete(); // reactions cascade via FK

        return $this->asJson(['status' => 200]);
    }

    /**
     * GET: returns rendered chat messages for a session (AJAX partial, all meetings).
     */
    public function actionChatMessages(int $id): string
    {
        $session = $this->svc->get($id, $this->contentContainer)
            ?? throw new NotFoundHttpException();

        if (!$session->canJoin()) {
            throw new ForbiddenHttpException();
        }

        $messages = SessionMeetingChat::findAllForSession($session->id)->all();

        return $this->renderPartial('@bbb/views/session/_chatMessages', [
            'messages' => $messages,
            'session'  => $session,
        ]);
    }

    private function notifySessionStarted(Session $session): void
    {
        $notification = SessionStarted::instance()
            ->from(Yii::$app->user->identity)
            ->about($session);

        // Explicit attendees and moderators (sendBulk auto-excludes the originator)
        $sessionUserQuery = User::find()
            ->innerJoin('bbb_session_user su', 'su.user_id = user.id')
            ->where(['su.session_id' => $session->id])
            ->andWhere(['OR', ['su.can_join' => 1], ['su.role' => 'moderator']]);

        $notification->sendBulk($sessionUserQuery);

        // Profile owner — only if not already covered by the SessionUser query
        $owner = $session->content->container;
        if (!($owner instanceof User) || $owner->id === Yii::$app->user->id) {
            return;
        }
        $ownerIsSessionUser = SessionUser::find()
            ->where(['session_id' => $session->id, 'user_id' => $owner->id])
            ->andWhere(['OR', ['can_join' => 1], ['role' => 'moderator']])
            ->exists();
        if (!$ownerIsSessionUser) {
            $notification->send($owner);
        }
    }
}
