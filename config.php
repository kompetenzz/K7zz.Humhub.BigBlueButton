<?php
use yii\base\Application;
use humhub\modules\dashboard\widgets\Sidebar as DashboardSidebar;
use humhub\modules\space\widgets\HeaderControlsMenu;
use humhub\modules\space\widgets\Menu;
use humhub\modules\space\widgets\Sidebar;
use humhub\modules\user\widgets\AccountMenu;
use humhub\modules\user\widgets\ProfileMenu;
use humhub\modules\user\widgets\ProfileSidebar;
use humhub\modules\admin\widgets\AdminMenu;
use humhub\widgets\TopMenu;
use k7zz\humhub\bbb\Events;


return [
    'id' => 'bbb',
    'class' => 'k7zz\humhub\bbb\Module',
    'namespace' => 'k7zz\humhub\bbb',
    'events' => [
        ['class' => Application::class, 'event' => Application::EVENT_BEFORE_REQUEST, 'callback' => [Events::class, 'onBeforeRequest']],
        ['class' => TopMenu::class, 'event' => TopMenu::EVENT_INIT, 'callback' => [Events::class, 'onTopMenuInit']],
        ['class' => Menu::class, 'event' => Menu::EVENT_INIT, 'callback' => [Events::class, 'onSpaceMenuInit']],
        ['class' => ProfileMenu::class, 'event' => ProfileMenu::EVENT_INIT, 'callback' => [Events::class, 'onProfileMenuInit']],
        ['class' => AccountMenu::class, 'event' => AccountMenu::EVENT_INIT, 'callback' => [Events::class, 'onAccountMenuInit']],
        ['class' => HeaderControlsMenu::class, 'event' => HeaderControlsMenu::EVENT_INIT, 'callback' => [Events::class, 'onSpaceHeaderControlsMenuInit']],
        ['class' => AdminMenu::class, 'event' => AdminMenu::EVENT_INIT, 'callback' => [Events::class, 'onAdminMenuInit']],
        ['class' => Sidebar::class, 'event' => Sidebar::EVENT_INIT, 'callback' => [Events::class, 'onSpaceSidebarInit']],
        ['class' => ProfileSidebar::class, 'event' => ProfileSidebar::EVENT_INIT, 'callback' => [Events::class, 'onProfileSidebarInit']],
        ['class' => DashboardSidebar::class, 'event' => DashboardSidebar::EVENT_INIT, 'callback' => [Events::class, 'onDashboardSidebarInit']],
        ['class' => 'humhub\modules\custom_pages\modules\template\services\ElementTypeService', 'event' => 'init', 'callback' => [Events::class, 'onCustomPagesElementTypeServiceInit']],
    ],
    'urlManagerRules' => [
        ['class' => 'k7zz\humhub\bbb\components\SessionUrlRule'],
        'bbb/webhook/receive'       => 'bbb/webhook/receive',
        'bbb/session/queue-chat'    => 'bbb/session/queue-chat',
        'bbb/session/pre-meeting-chats' => 'bbb/session/pre-meeting-chats',
    ]
];
