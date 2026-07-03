<?php

namespace k7zz\humhub\bbb\controllers;

use k7zz\humhub\bbb\services\WebhookProcessor;
use Yii;
use yii\filters\AccessControl;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;

/**
 * Receives BBB webhook events (bbb-webhooks service) and dispatches them
 * to WebhookProcessor. The endpoint is public — called server-to-server by BBB.
 */
class WebhookController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'actions' => ['receive'], 'roles' => ['?', '@']],
                ],
            ],
        ];
    }

    /**
     * Entry point for BBB webhook POSTs.
     * BBB sends: POST with body  event=<url-encoded-json>&timestamp=<ms>
     */
    public function actionReceive(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $raw = Yii::$app->request->post('event');
        if ($raw === null) {
            throw new BadRequestHttpException('Missing event payload.');
        }

        $events = json_decode($raw, true);
        if (!is_array($events)) {
            throw new BadRequestHttpException('Invalid event JSON.');
        }

        $processor = new WebhookProcessor();
        foreach ($events as $event) {
            $processor->process($event);
        }

        return $this->asJson(['status' => 'ok']);
    }
}
