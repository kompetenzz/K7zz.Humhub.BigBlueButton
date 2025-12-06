<?php

use yii\db\Migration;

class m251127_094117_bbb_add_camera_background_image extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1) Neue Spalte anlegen (nullable Integer für HumHub-File-ID)
        $this->addColumn('bbb_session', 'camera_bg_image_file_id', $this->integer()->null());

        // 2) ForeignKey auf die HumHub‐File‐Tabelle
        $this->addForeignKey(
            'fk_bbb_session_camera_bg_image_file',
            'bbb_session',
            'camera_bg_image_file_id',
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
        echo "m251127_094117_bbb_add_camera_background_image cannot be reverted.\n";

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251127_094117_bbb_add_camera_background_image cannot be reverted.\n";

        return false;
    }
    */
}
