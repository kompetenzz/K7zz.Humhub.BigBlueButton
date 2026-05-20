<?php

namespace k7zz\humhub\bbb\notifications;

use humhub\modules\notification\components\BaseNotification;
use humhub\modules\user\models\User;
use Yii;
use yii\helpers\Html;

class SessionStarted extends BaseNotification
{
    public $moduleId = 'bbb';

    protected function category()
    {
        $isUserContainer = $this->source
            && $this->source->content
            && $this->source->content->container instanceof User;
        return new SessionStartedCategory(['isUserContainer' => $isUserContainer]);
    }

    public function getUrl()
    {
        return $this->source->getUrl();
    }

    public function getMailSubject()
    {
        return Yii::t('BbbModule.base', '{displayName} started the session: {title}', [
            'displayName' => $this->originator->displayName,
            'title' => $this->source->title,
        ]);
    }

    public function html()
    {
        return '<i class="fa fa-phone"></i> ' . Yii::t('BbbModule.base', '{displayName} started the session: {title}', [
            'displayName' => Html::tag('strong', Html::encode($this->originator->displayName)),
            'title' => Html::encode($this->source->title),
        ]);
    }
}
