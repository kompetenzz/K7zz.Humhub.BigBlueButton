<?php

use yii\db\Migration;

class m250831_203232_bbb_add_public_token extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'bbb_session',
            'public_token',
            $this->string(64)->null()->unique()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('bbb_session', 'public_token');
        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250831_203232_bbb_add_public_token cannot be reverted.\n";

        return false;
    }
    */
}
