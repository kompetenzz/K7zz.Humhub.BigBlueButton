<?php
namespace k7zz\humhub\bbb\assets;

use Yii;
use yii\web\AssetBundle;

class BBBAssets extends AssetBundle
{
    public $sourcePath = '@bbb/resources';

    /**
     * @inheritdoc
     */
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

    public $js = [
        'js/Slugifyer.js',
        'js/Helpers.js'
    ];

    public $jsOptions = [
        'type' => 'module', // wichtig für ES6 import/export
    ];
}