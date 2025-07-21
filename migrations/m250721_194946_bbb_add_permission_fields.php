<?php

use yii\db\Migration;

class m250721_194946_bbb_add_permission_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'bbb_session',
            'join_can_start',
            $this->boolean()->notNull()->defaultValue(false)
        );
        $this->addColumn(
            'bbb_session',
            'join_can_moderate',
            $this->boolean()->notNull()->defaultValue(false)
        );
        $this->addColumn(
            'bbb_session',
            'has_waitingroom',
            $this->boolean()->notNull()->defaultValue(false)
        );
        $this->addColumn(
            'bbb_session',
            'allow_recording',
            $this->boolean()->notNull()->defaultValue(true)
        );
        $this->addColumn(
            'bbb_session',
            'mute_on_entry',
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
            'allow_recording'
        );
        $this->dropColumn(
            'bbb_session',
            'mute_on_entry'
        );
        $this->dropColumn(
            'bbb_session',
            'waitingroom'
        );
        $this->dropColumn(
            'bbb_session',
            'join_can_moderate'
        );
        $this->dropColumn(
            'bbb_session',
            'join_can_start'
        );
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250721_194946_bbb_add_permission_fields cannot be reverted.\n";

        return false;
    }
    */
}
