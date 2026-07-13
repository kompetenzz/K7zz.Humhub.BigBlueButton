<?php

use yii\db\Migration;

class uninstall extends Migration
{
    public function up()
    {
        // FK-safe order: children before parents
        $this->dropTable('bbb_session_chat_reaction');
        $this->dropTable('bbb_session_meeting_chat');
        $this->dropTable('bbb_session_meeting_join');
        $this->dropTable('bbb_session_meeting');
        $this->dropTable('bbb_recording_format');
        $this->dropTable('bbb_session_user');
        $this->dropTable('bbb_session');
    }

    public function down()
    {
        echo "uninstall does not support migration down.\n";
        return false;
    }

}