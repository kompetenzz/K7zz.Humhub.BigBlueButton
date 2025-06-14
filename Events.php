<?php

namespace humhub\modules\bbb;

use Yii;

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
}