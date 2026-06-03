<?php

namespace k7zz\humhub\bbb\extensions\custom_pages\elements;

use humhub\modules\custom_pages\modules\template\elements\BaseContentRecordsElement;
use humhub\modules\custom_pages\modules\template\elements\BaseElementVariable;
use k7zz\humhub\bbb\models\Session;
use Yii;
use yii\db\ActiveQuery;

/**
 * Custom Pages element for a list of BBB sessions.
 *
 * @property bool $onlyEnabled
 * @property bool $onlySidebar
 */
class BBBSessionsElement extends BaseContentRecordsElement
{
    public const FILTER_ENABLED = 'enabled';
    public const FILTER_SIDEBAR = 'sidebar';
    public const RECORD_CLASS = Session::class;
    public const SORT_TITLE = 'title';

    protected function getDynamicAttributes(): array
    {
        return parent::getDynamicAttributes();
    }

    public function getLabel(): string
    {
        return Yii::t('BbbModule.base', 'BBB Sessions');
    }

    public function attributeLabels()
    {
        return parent::attributeLabels();
    }

    public function getContentFilterOptions(): array
    {
        return array_merge([
            self::FILTER_ENABLED => Yii::t('BbbModule.base', 'Active sessions only'),
            self::FILTER_SIDEBAR => Yii::t('BbbModule.base', 'Sidebar sessions only'),
        ], parent::getContentFilterOptions());
    }

    protected function getQuery(): ActiveQuery
    {
        $query = parent::getQuery();

        // Always exclude soft-deleted sessions
        $query->andWhere(['bbb_session.deleted_at' => null]);

        if ($this->hasFilter(self::FILTER_ENABLED)) {
            $query->andWhere(['bbb_session.enabled' => 1]);
        }

        if ($this->hasFilter(self::FILTER_SIDEBAR)) {
            $query->andWhere(['bbb_session.show_in_sidebar' => 1]);
        }

        if ($this->sortOrder === self::SORT_TITLE) {
            $query->orderBy(['bbb_session.title' => SORT_ASC]);
        }

        return $query;
    }

    public function getTemplateVariable(): BaseElementVariable
    {
        return new BBBSessionsElementVariable($this);
    }

    protected function getSortOptions(): array
    {
        return array_merge(parent::getSortOptions(), [
            self::SORT_TITLE => Yii::t('BbbModule.base', 'By title (A–Z)'),
        ]);
    }
}
