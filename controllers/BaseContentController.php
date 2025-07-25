<?php

namespace k7zz\humhub\bbb\controllers;

use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\space\models\Space;
use k7zz\humhub\bbb\services\SessionService;
use Yii;
use yii\helpers\Url;

/**
 * Base controller for BBB content container controllers.
 *
 * Provides common properties and helpers for all BBB controllers
 * that operate in a content container context (space or user).
 */
abstract class BaseContentController extends ContentContainerController
{
    /**
     * Whether a content container is required for this controller.
     * @var bool
     */
    public $requireContainer = false;
    /**
     * Whether to hide the sidebar in views.
     * @var bool
     */
    public $hideSidebar = true;

    /**
     * The session service instance for BBB logic.
     * @var SessionService|null
     */
    protected ?SessionService $svc = null;

    /**
     * Initializes the controller and the session service.
     */
    public function init()
    {
        parent::init();
        $this->svc = Yii::createObject(SessionService::class);
    }

    /**
     * Helper to generate URLs in the context of the current content container.
     * @param string $url
     * @return string
     */
    protected function getUrl($url)
    {
        if ($this->contentContainer) {
            return $this->contentContainer->createUrl($url);
        }
        return Url::to($url);
    }

}
