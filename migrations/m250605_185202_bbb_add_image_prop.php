<?php

use yii\db\Migration;

class m250605_185202_bbb_add_image_prop extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $table = $this->db->getTableSchema('bbb_session', true);

        if ($table === null || !isset($table->columns['image_file_id'])) {
            // 1) Neue Spalte anlegen (nullable Integer für HumHub-File-ID)
            $this->addColumn('bbb_session', 'image_file_id', $this->integer()->null());

            // 2) ForeignKey auf die HumHub‐File‐Tabelle
            $this->addForeignKey(
                'fk_bbb_session_image_file',
                'bbb_session',
                'image_file_id',
                'file',
                'id',
                'SET NULL',    // Wenn das File gelöscht wird, wird image_file_id in bbb_session auf NULL gesetzt
                'CASCADE'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250605_185202_bbb_add_image_prop cannot be reverted.\n";

        return false;
    }
}
