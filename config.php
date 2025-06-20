<?php
use humhub\components\Application;
use humhub\modules\space\widgets\Menu;
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
    ],
    'urlManagerRules' => [
        ['class' => 'k7zz\humhub\bbb\components\SessionUrlRule'],
    ]
];
