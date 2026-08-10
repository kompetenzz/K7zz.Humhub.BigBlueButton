<?php

use k7zz\humhub\bbb\enums\Layouts;

use yii\db\Migration;

class m250901_093430_bbb_add_display_options extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $table = $this->db->getTableSchema('bbb_session', true);

        if ($table === null || !isset($table->columns['layout'])) {
            $in = "'" . implode("','", Layouts::values()) . "'";
            $this->addColumn(
                'bbb_session',
                'layout',
                "ENUM($in) NOT NULL DEFAULT '" . Layouts::CUSTOM_LAYOUT . "'"
            );
        }
    }

    public function safeDown()
    {
        $table = $this->db->getTableSchema('bbb_session', true);

        if ($table !== null && isset($table->columns['layout'])) {
            $this->dropColumn('bbb_session', 'layout');
        }
    }
}
