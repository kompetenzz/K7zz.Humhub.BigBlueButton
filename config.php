<?php
use humhub\components\Application;
use k7zz\humhub\bbb\Events;


return [
    'id' => 'bbb',
    'class' => 'k7zz\humhub\bbb\Module',
    'namespace' => 'k7zz\humhub\bbb',
    'events' => [
        ['class' => Application::class, 'event' => Application::EVENT_BEFORE_REQUEST, 'callback' => [Events::class, 'onBeforeRequest']]
    ],
    'urlManagerRules' => [
        'bbb/session/<action:\w+>/<slug:[a-zA-Z0-9\-]+>' => 'bbb/session/<action>'
    ]
];
