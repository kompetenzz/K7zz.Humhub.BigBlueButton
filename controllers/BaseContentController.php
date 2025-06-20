<?php

namespace k7zz\humhub\bbb\controllers;

use humhub\modules\content\components\ContentContainerController;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\space\models\Space;
use k7zz\humhub\bbb\services\SessionService;
use Yii;

abstract class BaseContentController extends ContentContainerController
{

    public $requireContainer = false;
    public $hideSidebar = true;

    protected ?string $scope = null;

    protected ?SessionService $svc = null;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->svc = Yii::createObject(SessionService::class);

        $this->scope = 'global';
        if ($this->contentContainer) {
            $this->scope = $this->contentContainer instanceof Space ? 'space' : 'user';
        }
    }

    public function redirect($url, $containerId = null, $statusCode = 302)
    {
        return Yii::$app->response->redirect($this->getUrl($url, $containerId), $statusCode);
    }

    protected function getUrl($url, $containerId = null)
    {
        if ($containerId) {
            $baseContainer = ContentContainer::findOne(['id' => $containerId]);
            $containerClass = $baseContainer->class;
            $container = $containerClass::find()->where(['id' => $containerId])->one();
        } else if ($this->contentContainer) {
            $container = $this->contentContainer;
        }
        if (isset($container)) {
            return $container->createUrl($url);
        }
        return Yii::$app->urlManager->createUrl($url);
    }
}
