<?php

namespace k7zz\humhub\bbb\controllers;

use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\space\models\Space;
use k7zz\humhub\bbb\services\SessionService;
use Yii;
use yii\helpers\Url;

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

    protected function getUrl($url, ContentActiveRecord $container = null)
    {
        if ($container) {
            return $container->createUrl($url);
        }
        return Url::to($url);
    }

}
