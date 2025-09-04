<?php

use yii\db\Migration;

class m250901_093502_bbb_add_presentation_file_prop extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('bbb_session', 'presentation_file_id', $this->integer()->null());

        // 2) ForeignKey auf die HumHub‐File‐Tabelle
        $this->addForeignKey(
            'fk_bbb_session_presentation_file',
            'bbb_session',
            'presentation_file_id',
            'file',
            'id',
            'SET NULL',    // Wenn das File gelöscht wird, wird image_file_id in bbb_session auf NULL gesetzt
            'CASCADE'
        );

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250901_093502_bbb_add_presentation_file_prop cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250901_093502_bbb_add_presentation_file_prop cannot be reverted.\n";

        return false;
    }
    */
}
