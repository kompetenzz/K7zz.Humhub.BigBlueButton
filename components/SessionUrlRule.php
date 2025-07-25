<?php

namespace k7zz\humhub\bbb\components;

use humhub\modules\content\components\ContentContainerActiveRecord;
use yii\base\Component;
use yii\helpers\BaseInflector;
use yii\web\UrlRuleInterface;
use yii\web\UrlManager;
use humhub\components\ContentContainerUrlRuleInterface;
use k7zz\humhub\bbb\models\Session;

/**
 * URL rule component for BBB session routes in HumHub.
 *
 * Handles pretty URLs for BBB session actions, both global and within content containers (spaces, users).
 * Converts session IDs to slugs (names) and vice versa for cleaner URLs.
 *
 * Implements both UrlRuleInterface and ContentContainerUrlRuleInterface.
 */
class SessionUrlRule extends Component implements UrlRuleInterface, ContentContainerUrlRuleInterface
{
    /**
     * Prefix for all BBB session routes.
     * @var string
     */
    private string $routePrefix = 'bbb/session/';

    /**
     * @inheritdoc
     * Handles global (non-container) URLs for BBB sessions.
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
    }

    /**
     * @inheritdoc
     * Handles parsing of global BBB session URLs.
     * Example: 'bbb/session/<action>/<slug>' => 'bbb/session/<action>'
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
     * Handles content container URLs for BBB sessions.
     * Example: 's/<space>/bbb/session/<action>/<slug>' => 'bbb/session/<action>'
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
     * Handles parsing of content container BBB session URLs.
     * Example: 's/<space>/bbb/session/<action>/<slug>' => 'bbb/session/<action>'
     * @throws \Exception
     */
    public function parseContentContainerRequest(ContentContainerActiveRecord $container, UrlManager $manager, string $containerUrlPath, array $urlParams)
    {
        if (strpos($containerUrlPath, $this->routePrefix) === 0) {
            return $this->getSessionRoute($containerUrlPath, $urlParams);
        }

        return false;
    }

    /**
     * Helper to resolve a session route from a path and parameters.
     * Converts a slug (name) back to a session ID for routing.
     * @param string $path
     * @param array $getParams
     * @return array|bool
     */
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
