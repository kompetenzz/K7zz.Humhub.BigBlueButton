<?php

return [
    'components' => [
        'db' => [
            'dsn'      => 'mysql:host=db;dbname=humhub_test',
            'username' => 'root',
            'password' => 'fbdf05aa7c',
        ],
    ],
    'params' => [
        'moduleAutoloadPaths' => ['@app/modules', '@humhub/modules'],
    ],
];
