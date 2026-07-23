<?php

use yii\db\Migration;

class m260703_000000_bbb_session_meeting extends Migration
{
    public function safeUp()
    {
        $session = $this->db->getTableSchema('bbb_session', true);
        if ($session === null || !isset($session->columns['integrate_bbb_chat'])) {
            $this->addColumn('bbb_session', 'integrate_bbb_chat', $this->boolean()->notNull()->defaultValue(false));
        }

        if ($this->db->getTableSchema('bbb_session_meeting', true) === null) {
            $this->createTable('bbb_session_meeting', [
                'id'                   => $this->primaryKey(),
                'session_id'           => $this->integer()->notNull(),
                'internal_meeting_id'  => $this->string(255)->notNull(),
                'started_at'           => $this->integer()->notNull(),
                'ended_at'             => $this->integer()->null(),
                'created_at'           => $this->integer()->notNull(),
            ]);

            $this->createIndex('idx_bbb_session_meeting_session', 'bbb_session_meeting', 'session_id');
            $this->createIndex('idx_bbb_session_meeting_internal', 'bbb_session_meeting', 'internal_meeting_id', true);
        }

        if ($this->db->getTableSchema('bbb_session_meeting_chat', true) === null) {
            $this->createTable('bbb_session_meeting_chat', [
                'id'               => $this->primaryKey(),
                'session_meeting_id' => $this->integer()->notNull(),
                'user_id'          => $this->integer()->null(),
                'sender_name'      => $this->string(255)->notNull()->defaultValue(''),
                'message'          => $this->text()->notNull(),
                'source'           => $this->string(10)->notNull()->defaultValue('humhub'), // humhub|bbb
                'sent_at'          => $this->integer()->null(),   // null = noch nicht injiziert
                'created_at'       => $this->integer()->notNull(),
            ]);

            $this->createIndex('idx_bbb_smc_meeting', 'bbb_session_meeting_chat', 'session_meeting_id');
        }

        if ($this->db->getTableSchema('bbb_session_meeting_join', true) === null) {
            $this->createTable('bbb_session_meeting_join', [
                'id'                   => $this->primaryKey(),
                'session_meeting_id'   => $this->integer()->notNull(),
                'user_id'              => $this->integer()->null(),
                'bbb_internal_user_id' => $this->string(64)->notNull()->defaultValue(''),
                'display_name'         => $this->string(255)->notNull()->defaultValue(''),
                'role'                 => $this->string(20)->notNull()->defaultValue('viewer'),
                'joined_at'            => $this->integer()->notNull(),
                'left_at'              => $this->integer()->null(),
            ]);

            $this->createIndex('idx_bbb_smj_meeting', 'bbb_session_meeting_join', 'session_meeting_id');
            $this->createIndex('idx_bbb_smj_internal_user', 'bbb_session_meeting_join', ['session_meeting_id', 'bbb_internal_user_id'], true);
        }
    }

    public function safeDown()
    {
        $this->dropTable('bbb_session_meeting_join');
        $this->dropTable('bbb_session_meeting_chat');
        $this->dropTable('bbb_session_meeting');
        $this->dropColumn('bbb_session', 'integrate_bbb_chat');
    }
}
