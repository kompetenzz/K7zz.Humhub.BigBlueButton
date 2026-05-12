<?php

use yii\db\Migration;

class m260512_000000_bbb_add_start_minimized_options extends Migration
{
    public function safeUp()
    {
        $this->addColumn('bbb_session', 'start_chat_minimized', $this->boolean()->notNull()->defaultValue(false));
        $this->addColumn('bbb_session', 'start_participants_minimized', $this->boolean()->notNull()->defaultValue(false));
    }

    public function safeDown()
    {
        $this->dropColumn('bbb_session', 'start_chat_minimized');
        $this->dropColumn('bbb_session', 'start_participants_minimized');
    }
}
