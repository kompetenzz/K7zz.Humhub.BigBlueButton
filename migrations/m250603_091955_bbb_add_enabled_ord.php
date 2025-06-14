<?php

use yii\db\Migration;

class m250603_091955_bbb_add_enabled_ord extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'bbb_session',
            'enabled',
            $this->boolean()->notNull()->defaultValue(true)
        );
        $this->addColumn(
            'bbb_session',
            'ord',
            $this->integer()->notNull()->defaultValue(0)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250603_091955_bbb_add_enabled_ord cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250603_091955_bbb_add_enabled_ord cannot be reverted.\n";

        return false;
    }
    */
}
