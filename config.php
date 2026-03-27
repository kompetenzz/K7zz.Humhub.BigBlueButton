<?php
use yii\base\Application;
use humhub\modules\dashboard\widgets\Sidebar as DashboardSidebar;
use humhub\modules\space\widgets\Menu;
use humhub\modules\space\widgets\Sidebar;
use humhub\modules\user\widgets\ProfileMenu;
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
        ['class' => Sidebar::class, 'event' => Sidebar::EVENT_INIT, 'callback' => [Events::class, 'onSpaceSidebarInit']],
        ['class' => DashboardSidebar::class, 'event' => DashboardSidebar::EVENT_INIT, 'callback' => [Events::class, 'onDashboardSidebarInit']],
    ],
    'urlManagerRules' => [
        ['class' => 'k7zz\humhub\bbb\components\SessionUrlRule'],
    ]
];
