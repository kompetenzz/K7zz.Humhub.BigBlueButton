<?php
namespace k7zz\humhub\bbb\assets;

use Yii;
use yii\helpers\Url;
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
            'css/*.css',
        ],
    ];

    /** @inheritdoc */
    public $css = [
        'css/bbb.css',
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

    /**
     * Returns the public URL of a published file of this bundle.
     *
     * Since HumHub 1.19 the asset manager's base URL may be an unresolved alias
     * (e.g. `@web/assets`), so `$baseUrl` must not be used directly in HTML.
     *
     * @param string $path path relative to the resources directory, e.g. `images/conference.png`
     * @return string
     */
    public function getAssetUrl(string $path): string
    {
        return Url::to($this->baseUrl . '/' . ltrim($path, '/'));
    }
}