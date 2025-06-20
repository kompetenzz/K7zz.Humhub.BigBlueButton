<?php

/**
 * THis module provides integration with BigBlueButton for video conferencing.
 * It allows users to create and join meetings, manage permissions, and handle meeting data.
 * It includes controllers for managing meetings, a service for handling meeting logic,
 * and permissions for starting and joining meetings.
 */

namespace k7zz\humhub\bbb;

use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\space\models\Space;
use humhub\modules\user\components\User;
use k7zz\humhub\bbb\models\Session;
use Yii;
use humhub\modules\content\components\ContentContainerModule;
use k7zz\humhub\bbb\permissions\{
    Admin,
    StartSession,
    JoinSession
};
use k7zz\humhub\bbb\services\SessionService;
use yii\helpers\Url;

class Module extends ContentContainerModule
{
    public $guid = 'bbb';                     // ganz wichtig
    public $controllerNamespace = __NAMESPACE__ . '\controllers';

    public function init()
    {
        parent::init();
        Yii::$container->set(SessionService::class);
    }

    public function getContentContainerTypes()
    {
        // This module can only be installed on spaces
        return [Space::class, User::class];
    }

    /**
     * @inheritdoc
     */
    public function getContainerPermissions($contentContainer = null)
    {
        return [new Admin(), new StartSession(), new JoinSession()];
    }

    /**
     * @inheritdoc
     */
    public function getContentClasses(): array
    {
        return [Session::class];
    }


    public function getPermissions($contentContainer = null)
    {
        return [new Admin(), new StartSession(), new JoinSession()];
    }

    public function getConfigUrl()
    {
        return Url::to(['/bbb/config/']);
    }
    /**
     * @inheritdoc
     */
    public function getContentContainerConfigUrl(ContentContainerActiveRecord $container)
    {
        return $container->createUrl('/bbb/container-config');
    }
    /**
     * @inheritdoc
     */
    public function getContentContainerDescription(ContentContainerActiveRecord $container)
    {
        if ($container instanceof Space) {
            return Yii::t('BbbModule.base', 'Adds sessions to this space.');
        } elseif ($container instanceof User) {
            return Yii::t('BbbModule.base', 'Adds sessions to your profile.');
        }
        return Yii::t('BbbModule.base', 'Adds sessions to this container.');
    }
}
