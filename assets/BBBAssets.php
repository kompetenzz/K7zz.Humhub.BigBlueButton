<?php
namespace k7zz\humhub\bbb\assets;

use Yii;
use yii\web\AssetBundle;

/**
 * Asset bundle for the BigBlueButton module.
 *
 * Registers JavaScript and image resources for use in the module's views.
 *
 * - Publishes JS and image files from the resources directory
 * - Sets JS type to module for ES6 support
 */
class BBBAssets extends AssetBundle
{
    /** @inheritdoc */
    public $sourcePath = '@bbb/resources';

    /** @inheritdoc */
    public $publishOptions = [
        // TIPP: Change forceCopy to true when testing your js in order to rebuild
        // this assets on every request (otherwise they will be cached)
        'forceCopy' => true,
        'only' => [
            'images/*.png',
            'images/*.jpg',
            'images/*.gif',
            'js/*.js',
        ],
    ];

    /** @inheritdoc */
    public $js = [
        'js/Slugifyer.js',
        'js/Helpers.js'
    ];

    /** @inheritdoc */
    public $jsOptions = [
        'type' => 'module', // wichtig für ES6 import/export
    ];
}