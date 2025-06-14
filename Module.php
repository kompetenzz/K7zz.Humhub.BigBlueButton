<?php

/**
 * THis module provides integration with BigBlueButton for video conferencing.
 * It allows users to create and join meetings, manage permissions, and handle meeting data.
 * It includes controllers for managing meetings, a service for handling meeting logic,
 * and permissions for starting and joining meetings.
 */

namespace humhub\modules\bbb;

use Yii;
use humhub\components\Module as BaseModule;
use humhub\modules\bbb\permissions\{
    Admin,
    StartSession,
    JoinSession
};
use \humhub\modules\bbb\services\SessionService;
use yii\helpers\Url;

class Module extends BaseModule
{
    public $controllerNamespace = 'humhub\\modules\\bbb\\controllers';

    public function init()
    {
        parent::init();
        Yii::$container->set(SessionService::class);
    }

    public function getPermissions($contentContainer = null)
    {
        return [new Admin(), new StartSession(), new JoinSession()];
    }

    public function getConfigUrl()
    {
        return Url::to(['/bbb/config/']);
    }

    public function getUrlRules()
    {
        return [
            'bbb/session/<action:\w+>/<slug:[a-zA-Z0-9\-]+>' => 'bbb/session/<action>',
        ];
    }
}
