<?php

namespace k7zz\humhub\bbb\helpers;

use humhub\components\ActiveRecord;
use humhub\modules\content\components\ContentContainerActiveRecord;
use yii\helpers\Url as BaseUrl;

class Url extends BaseUrl
{
    public const ROUTES = [
        'CONFIG' => '/bbb/config',
        'EDIT' => '/bbb/session/edit',
        'DELETE' => '/bbb/session/delete',
        'OVERVIEW' => '/bbb/sessions'
    ];

    private static function create($route, $params = [], ContentContainerActiveRecord $container = null)
    {
        if ($container) {
            return $container->createUrl($route, $params);
        } else {
            $params[0] = $route;
            return static::to($params);
        }
    }
    /**
     * @return string
     */
    public static function toModuleConfig()
    {
        return static::toRoute(static::ROUTES['CONFIG']);
    }


    public static function toEditSession($id, ContentContainerActiveRecord $container = null)
    {
        if ($id instanceof ActiveRecord) {
            $id = $id->id;
        }

        return static::create(static::ROUTES['EDIT'], ['id' => $id], $container);
    }

    public static function toOverview(ContentContainerActiveRecord $container = null)
    {
        return static::create(static::ROUTES['OVERVIEW'], [], $container);
    }

    public static function toDeleteSession($id, ContentContainerActiveRecord $container = null)
    {
        if ($id instanceof ActiveRecord) {
            $id = $id->id;
        }

        return static::create(static::ROUTES['DELETE'], ['id' => $id], $container);
    }

}
