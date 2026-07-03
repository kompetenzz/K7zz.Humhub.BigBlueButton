<?php

$testRoot = dirname(__DIR__);
\Codeception\Configuration::append(['test_root' => $testRoot]);

$moduleConfig = require $testRoot . '/config/test.php';
$humhubPath   = $moduleConfig['humhub_root'] ?? dirname(__DIR__, 5);

\Codeception\Configuration::append(['humhub_root' => $humhubPath]);

$globalConfig = require $humhubPath . '/protected/humhub/tests/codeception/_loadConfig.php';
require $globalConfig['humhub_root'] . '/protected/humhub/tests/codeception/_bootstrap.php';

$bbbPath = dirname($testRoot);
Yii::setAlias('@bbb', $bbbPath);
Yii::setAlias('@humhub/modules/bbb', $bbbPath);

// Register the k7zz\humhub\bbb namespace so Yii/PHP can autoload BBB module classes.
\Codeception\Util\Autoload::addNamespace('k7zz\\humhub\\bbb', $bbbPath);

// Load the BBB module's own vendor dependencies (BigBlueButton library etc.).
$bbbVendorAutoload = $bbbPath . '/vendor/autoload.php';
if (file_exists($bbbVendorAutoload)) {
    require_once $bbbVendorAutoload;
}
