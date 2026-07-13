<?php

use yii\db\Migration;

/**
 * Track message edits so the chat can show an "(edited)" marker.
 */
class m260707_000000_bbb_chat_edited_at extends Migration
{
    public function safeUp()
    {
        $this->addColumn('bbb_session_meeting_chat', 'edited_at', $this->integer()->null()->after('sent_at'));
    }

    public function safeDown()
    {
        $this->dropColumn('bbb_session_meeting_chat', 'edited_at');
    }
}
