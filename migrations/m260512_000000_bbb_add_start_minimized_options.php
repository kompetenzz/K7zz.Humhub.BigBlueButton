<?php

use yii\db\Migration;

class m260512_000000_bbb_add_start_minimized_options extends Migration
{
    public function safeUp()
    {
        $table = $this->db->getTableSchema('bbb_session', true);

        if ($table === null || !isset($table->columns['start_chat_minimized'])) {
            $this->addColumn('bbb_session', 'start_chat_minimized', $this->boolean()->notNull()->defaultValue(false));
        }
        if ($table === null || !isset($table->columns['start_participants_minimized'])) {
            $this->addColumn('bbb_session', 'start_participants_minimized', $this->boolean()->notNull()->defaultValue(false));
        }
    }

    public function safeDown()
    {
        $table = $this->db->getTableSchema('bbb_session', true);
        if ($table === null) {
            return;
        }

        if (isset($table->columns['start_chat_minimized'])) {
            $this->dropColumn('bbb_session', 'start_chat_minimized');
        }
        if (isset($table->columns['start_participants_minimized'])) {
            $this->dropColumn('bbb_session', 'start_participants_minimized');
        }
    }
}
