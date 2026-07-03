<?php

/**
 * BBB module unit-test config overrides.
 *
 * Returned by HumHubTestConfiguration::getSuiteConfig('unit') as the third
 * merge argument. Must NOT re-require common.php — getSuiteConfig already
 * starts from HumHub's default unit config (which includes common.php).
 * Re-requiring it would duplicate integer-keyed arrays (e.g. the DB
 * 'on afterOpen' handler), causing ArrayHelper::merge to append them into
 * 4-element arrays that fail call_user_func().
 */

return [
    'params' => [
        // Keep only core module paths — no /data/modules-custom to avoid
        // loading every custom module and exhausting memory.
        'moduleAutoloadPaths' => ['@app/modules', '@humhub/modules'],
    ],
    'components' => [
        'assetManager' => [
            'basePath' => '@runtime/assets',
            'bundles'  => false,
        ],
        // Replace web session with an in-memory stub to avoid
        // session_set_save_handler() failures in CLI when a native PHP
        // session is already active (triggered by admin\Events::onSwitchUser).
        'session' => [
            'class' => 'bbb\ArraySession',
        ],
    ],
    'modules' => [
        'bbb' => ['class' => 'k7zz\humhub\bbb\Module'],
    ],
];
