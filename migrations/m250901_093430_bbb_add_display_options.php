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
        $in = "'" . implode("','", Layouts::values()) . "'";
        $this->addColumn(
            'bbb_session',
            'layout',
            "ENUM($in) NOT NULL DEFAULT '" . Layouts::CUSTOM_LAYOUT . "'"
        );
    }

    public function safeDown()
    {
        $this->dropColumn('bbb_session', 'layout');
    }
    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250901_093430_bbb_add_display_options cannot be reverted.\n";

        return false;
    }
    */
}
