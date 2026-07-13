<?php

use yii\db\Migration;

/**
 * Allows chat messages to be queued before a meeting starts.
 *
 * Off-meeting messages have session_meeting_id = NULL and reference the session
 * directly via session_id. Once the meeting starts they get injected into BBB
 * and their session_meeting_id is filled in.
 */
class m260703_010000_bbb_chat_premeeting extends Migration
{
    public function safeUp()
    {
        // Make session_meeting_id nullable (off-meeting messages have no meeting yet)
        $this->alterColumn('bbb_session_meeting_chat', 'session_meeting_id', $this->integer()->null());

        // Add direct session reference for off-meeting messages
        $this->addColumn('bbb_session_meeting_chat', 'session_id', $this->integer()->null()->after('id'));
        $this->addColumn('bbb_session_meeting_chat', 'user_id_queued', $this->integer()->null()->after('session_id'));

        $this->createIndex('idx_bbb_smc_session', 'bbb_session_meeting_chat', 'session_id');
    }

    public function safeDown()
    {
        $this->dropIndex('idx_bbb_smc_session', 'bbb_session_meeting_chat');
        $this->dropColumn('bbb_session_meeting_chat', 'user_id_queued');
        $this->dropColumn('bbb_session_meeting_chat', 'session_id');
        $this->alterColumn('bbb_session_meeting_chat', 'session_meeting_id', $this->integer()->notNull());
    }
}
