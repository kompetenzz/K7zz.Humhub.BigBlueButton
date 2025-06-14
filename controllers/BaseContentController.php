<?php

namespace humhub\modules\bbb\controllers;

use humhub\modules\content\components\ContentContainerController;
use humhub\modules\bbb\services\SessionService;
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
