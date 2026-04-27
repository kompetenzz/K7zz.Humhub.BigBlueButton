<?php

namespace k7zz\humhub\bbb;

use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\helpers\ControllerHelper;
use humhub\modules\space\widgets\HeaderControlsMenu;
use humhub\modules\space\widgets\Sidebar;
use humhub\modules\ui\menu\MenuLink;
use humhub\modules\ui\menu\widgets\LeftNavigation;
use humhub\modules\user\widgets\AccountTopMenu;
use humhub\widgets\TopMenu;
use k7zz\humhub\bbb\permissions\Admin;
use k7zz\humhub\bbb\widgets\SidebarSessionWidget;
use k7zz\humhub\bbb\models\forms\ContainerSettingsForm;
use Yii;
use yii\helpers\Html;

class Events
{
    public static function onBeforeRequest()
    {
        try {
            static::registerAutoloader();
        } catch (\Throwable $e) {
            Yii::error($e);
        }

    }

    /**
     * Register composer autoloader when Reader not found
     */
    public static function registerAutoloader()
    {
        // Only if composer autoloader needed
        require Yii::getAlias('@bbb/vendor/autoload.php');
    }

    private static function addNavItem(
        $menu,
        $label,
        ?ContentContainerActiveRecord $container = null,
        int $order = 0
    ): void {
        $url = $container ? $container->createUrl('/bbb/sessions') :
            '/bbb/sessions';
        $is_active = ControllerHelper::isActivePath('bbb', ['sessions', 'session']);
        if ($container) {
            $is_active = $is_active && Yii::$app->controller->contentContainer &&
                Yii::$app->controller->contentContainer->id === $container->id;
        } else {
            $is_active = $is_active && Yii::$app->controller->contentContainer === null;
        }
        $menu->addEntry(new MenuLink([
            'id' => 'bbb-sessions-link',
            'label' => Html::encode($label),
            'url' => $url,
            'icon' => 'video-camera',
            'isActive' => $is_active,
            'sortOrder' => $order ?: 1000,

        ]));
    }
    public static function initNav(TopMenu $menu)
    {
        $addNavigationEntry = Yii::$app->getModule('bbb')->settings->get('addNavItem', true);

        if ($addNavigationEntry) {
            self::addNavItem(
                $menu,
                Yii::$app->getModule('bbb')->settings->get('navItemLabel', 'Live Sessions'),
            );
        }
    }


    /**
     * Initialize Space/Profile menu items
     *
     * @param ContentContainerActiveRecord $container
     * @param LeftNavigation $menu
     */
    public static function initContainerNav(ContentContainerActiveRecord $container, LeftNavigation $menu)
    {
        if (empty($container) || !$container->moduleManager->isEnabled('bbb')) {
            return;
        }

        $addNavigationEntry = Yii::$app->getModule('bbb')->settings->contentContainer($container)->get('addNavItem', true);

        if ($addNavigationEntry) {
            $settings = new ContainerSettingsForm(['contentContainer' => $container]);
            self::addNavItem(
                $menu,
                $settings->navItemLabel,
                $container
            );
        }
    }

    public static function onTopMenuInit($event)
    {
        try {
            self::initNav($event->sender);
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    public static function onSpaceMenuInit($event)
    {
        try {
            /* @var Menu $spaceMenu */
            $spaceMenu = $event->sender;
            self::initContainerNav($spaceMenu->space, $spaceMenu);
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    public static function onProfileMenuInit($event)
    {
        try {
            /* @var ProfileMenu $profileMenu */
            $profileMenu = $event->sender;
            self::initContainerNav($profileMenu->user, $profileMenu);
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    public static function onSpaceHeaderControlsMenuInit($event)
    {
        try {
            /** @var HeaderControlsMenu $menu */
            $menu = $event->sender;
            $space = $menu->space;

            if (empty($space) || !$space->moduleManager->isEnabled('bbb')) {
                return;
            }

            $addNavItem = Yii::$app->getModule('bbb')->settings->contentContainer($space)->get('addNavItem', true);
            if ($addNavItem) {
                return;
            }

            if (
                !$space->can(Admin::class)
                && !Yii::$app->user->isAdmin()
            ) {
                return;
            }

            $settings = new ContainerSettingsForm(['contentContainer' => $space]);
            $menu->addEntry(new MenuLink([
                'label' => Html::encode($settings->navItemLabel),
                'url' => $space->createUrl('/bbb/sessions'),
                'icon' => 'video-camera',
                'sortOrder' => 500,
            ]));
        } catch (\Throwable $e) {
            Yii::error($e, 'bbb');
        }
    }

    public static function onAccountTopMenuInit($event)
    {
    }

    public static function onAdminMenuInit($event)
    {
        try {
            $addNavItem = Yii::$app->getModule('bbb')->settings->get('addNavItem', true);
            if ($addNavItem) {
                return;
            }

            if (
                !Yii::$app->user->isAdmin()
                && !Yii::$app->user->can(Admin::class)
            ) {
                return;
            }

            /** @var AdminMenu $menu */
            $menu = $event->sender;
            $menu->addEntry(new MenuLink([
                'label' => Html::encode(Yii::$app->getModule('bbb')->settings->get('navItemLabel', 'Live Sessions')),
                'url' => ['/bbb/sessions'],
                'icon' => 'video-camera',
                'isActive' => ControllerHelper::isActivePath('bbb', ['sessions', 'session']),
                'sortOrder' => 350,
            ]));
        } catch (\Throwable $e) {
            Yii::error($e, 'bbb');
        }
    }

    public static function onDashboardSidebarInit($event)
    {
        try {
            $event->sender->addWidget(
                SidebarSessionWidget::class,
                ['contentContainer' => null],
                ['sortOrder' => 10]
            );
        } catch (\Throwable $e) {
            Yii::error($e, 'bbb');
        }
    }

    public static function onSpaceSidebarInit($event)
    {
        try {
            /** @var Sidebar $sidebar */
            $sidebar = $event->sender;
            if (empty($sidebar->space) || !$sidebar->space->moduleManager->isEnabled('bbb')) {
                return;
            }
            $settings = new ContainerSettingsForm(['contentContainer' => $sidebar->space]);
            $sidebar->addWidget(
                SidebarSessionWidget::class,
                ['contentContainer' => $sidebar->space],
                ['sortOrder' => $settings->sidebarSortOrder]
            );
        } catch (\Throwable $e) {
            Yii::error($e, 'bbb');
        }
    }

}