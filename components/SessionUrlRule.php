<?php

namespace k7zz\humhub\bbb\components;

use humhub\modules\content\components\ContentContainerActiveRecord;
use yii\base\Component;
use yii\helpers\BaseInflector;
use yii\web\UrlRuleInterface;
use yii\web\UrlManager;
use humhub\components\ContentContainerUrlRuleInterface;
use k7zz\humhub\bbb\models\Session;

class SessionUrlRule extends Component implements UrlRuleInterface, ContentContainerUrlRuleInterface
{
    private string $routePrefix = 'bbb/session/';

    /**
     * @inheritdoc
     */
    public function createUrl($manager, $route, $params)
    {
        // If in content container
        if (isset($params['cguid'])) {
            return false;
        }

        if (strpos($route, $this->routePrefix) !== 0 || !isset($params['id'])) {
            // If the route does not start with the prefix or does not have an id parameter,
            // we do not handle it here.
            return false;
        }

        $session = Session::findOne(['id' => $params['id']]);

        if ($session === null) {
            // If the session with the given id does not exist, we do not handle it here.
            return false;
        }

        unset($params['id']);
        $url = $route . '/' . $session->name;

        if (!empty($params) && ($query = http_build_query($params)) !== '') {
            $url .= '?' . $query;
        }
        return $url;


        //[$action, $name] = explode('/', substr($route, strlen($this->routePrefix)));
    }

    /**
     * @inheritdoc
     * Handles:
     *        'bbb/session/<action:\w+>/<slug:[a-zA-Z0-9\-]+>' => 'bbb/session/<action>',
     */
    public function parseRequest($manager, $request)
    {
        $pathInfo = $request->getPathInfo();
        if (strpos($pathInfo, $this->routePrefix) === 0) {
            return $this->getSessionRoute($pathInfo, $request->get());
        }
        return false;
    }


    /**
     * @inheritdoc
     */
    public function createContentContainerUrl(UrlManager $manager, string $containerUrlPath, string $route, array $params)
    {
        if (strpos($route, $this->routePrefix) !== 0 || !isset($params['id'])) {
            // If the route does not start with the prefix or does not have an id parameter,
            // we do not handle it here.
            return false;
        }

        $session = Session::findOne(['id' => $params['id']]);

        if ($session === null) {
            // If the session with the given id does not exist, we do not handle it here.
            return false;
        }

        unset($params['id']);
        $url = $containerUrlPath . '/' . $route . '/' . $session->name;
        if (!empty($params) && ($query = http_build_query($params)) !== '') {
            $url .= "?$query";
        }
        return $url;
    }

    /**
     * @inheritdoc
     * @throws Exception
     * 
     * Handles:
     *         's/<space:[\w\-]+>/bbb/session/<action:\w+>/<slug:[a-zA-Z0-9\-]+>' => 'bbb/session/<action>',
     */
    public function parseContentContainerRequest(ContentContainerActiveRecord $container, UrlManager $manager, string $containerUrlPath, array $urlParams)
    {
        if (strpos($containerUrlPath, $this->routePrefix) === 0) {
            return $this->getSessionRoute($containerUrlPath, $urlParams);
        }

        return false;
    }

    private function getSessionRoute(string $path, array $getParams): array|bool
    {
        $parts = explode('/', $path);
        $action = $parts[2] ?? null;
        $name = $parts[3] ?? null;
        $session = Session::find()
            ->where(['name' => $name])
            ->one();
        if (isset($action) && isset($name) && $session !== null) {
            $route = $this->routePrefix . $action;
            $params = $getParams;
            $params['id'] = $session->id;
            return [$route, $params];
        }

        return false;
    }

}
