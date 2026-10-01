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
use humhub\modules\user\models\User;
use k7zz\humhub\bbb\models\Session;
use Yii;
use humhub\modules\content\components\ContentContainerModule;
use k7zz\humhub\bbb\permissions\{
    ManageSession,
    StartSession,
    JoinSession
};
use k7zz\humhub\bbb\services\SessionService;
use yii\helpers\Url;

class Module extends ContentContainerModule
{
    public $guid = 'bbb';
    public $controllerNamespace = __NAMESPACE__ . '\controllers';
    public $resourcesPath = 'resources';

    public function init()
    {
        parent::init();
        Yii::$container->set(SessionService::class);
    }

    public function getContentContainerTypes()
    {
        return [
            Space::class,
            User::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getContainerPermissions($contentContainer = null)
    {
        return [new ManageSession(), new StartSession(), new JoinSession()];
    }

    /**
     * @inheritdoc
     */
    public function getContentClasses(): array
    {
        return [Session::class];
    }

    /**
     * @inheritdoc
     *
     * A session is a {@link \humhub\modules\content\components\ContentActiveRecord}, so every
     * session owns a record in the `content` table plus everything attached to it:
     * comments, likes, follows, files, activities, notifications and the search index.
     *
     * `parent::disable()` only runs the uninstall migration (which drops the `bbb_*`
     * tables) and removes the module settings - it does not touch content. Deleting
     * the sessions first is therefore up to us, otherwise all of the above is
     * orphaned and stays in the database for good.
     */
    public function disable()
    {
        $this->deleteSessions();

        // Has to run last, it drops the module tables.
        return parent::disable();
    }

    /**
     * @inheritdoc
     *
     * Disabling the module on a single space or profile drops no table at all, so
     * without this the container's sessions would keep showing up in its stream, on
     * the dashboard and in the search index.
     */
    public function disableContentContainer(ContentContainerActiveRecord $container)
    {
        $this->deleteSessions($container);

        parent::disableContentContainer($container);
    }

    /**
     * Hard-deletes sessions together with their content records. With a container
     * given only that container's sessions are removed, otherwise all of them -
     * global sessions (those without a container) included.
     *
     * `Session::find()` is used without `readable()` on purpose: that way drafts and
     * already soft-deleted sessions are covered as well.
     */
    private function deleteSessions(?ContentContainerActiveRecord $container = null): void
    {
        $query = Session::find();

        if ($container !== null) {
            $query->contentContainer($container);
        }

        foreach ($query->all() as $session) {
            /** @var Session $session */
            if (!$session->hardDelete()) {
                Yii::error('Could not delete session ' . $session->id . ' while disabling the module.', 'bbb');
            }
        }
    }


    public function getPermissions($contentContainer = null)
    {
        return [new ManageSession(), new StartSession(), new JoinSession()];
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
