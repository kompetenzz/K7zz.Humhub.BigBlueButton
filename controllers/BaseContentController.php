<?php

namespace k7zz\humhub\bbb\controllers;

use humhub\modules\content\components\ContentContainerController;
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
        $this->scope = $this->contentContainer
            ? $this->contentContainer->getContainerType()
            : 'global';
    }
}
