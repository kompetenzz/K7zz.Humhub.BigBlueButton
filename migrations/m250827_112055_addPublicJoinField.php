<?php

use yii\db\Migration;

class m250827_112055_addPublicJoinField extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'bbb_session',
            'public_join',
            $this->boolean()->notNull()->defaultValue(false)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn(
            'bbb_session',
            'public_join'
        );

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250827_112055_addPublicJoinField cannot be reverted.\n";

        return false;
    }
    */
}
