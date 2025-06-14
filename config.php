<?php
use humhub\components\Application;
use humhub\modules\bbb\Events;


return [
    'id' => 'bbb',
    'class' => 'humhub\modules\bbb\Module',
    'namespace' => 'humhub\modules\bbb',
    'events' => [
        ['class' => Application::class, 'event' => Application::EVENT_BEFORE_REQUEST, 'callback' => [Events::class, 'onBeforeRequest']]
    ]
];
