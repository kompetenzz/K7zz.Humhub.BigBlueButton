<?php

use yii\db\Migration;

class m260512_020000_bbb_add_start_presentation_hidden extends Migration
{
    public function safeUp()
    {
        $table = $this->db->getTableSchema('bbb_session', true);

        if ($table === null || !isset($table->columns['start_presentation_hidden'])) {
            $this->addColumn('bbb_session', 'start_presentation_hidden', $this->boolean()->notNull()->defaultValue(false));
        }
    }

    public function safeDown()
    {
        $table = $this->db->getTableSchema('bbb_session', true);

        if ($table !== null && isset($table->columns['start_presentation_hidden'])) {
            $this->dropColumn('bbb_session', 'start_presentation_hidden');
        }
    }
}
