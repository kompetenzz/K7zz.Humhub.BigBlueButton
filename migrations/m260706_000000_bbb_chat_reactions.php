<?php

use yii\db\Migration;

/**
 * Emoji reactions on chat messages (HumHub-side only — BBB has no reaction API,
 * so reactions are not visible inside a running meeting).
 */
class m260706_000000_bbb_chat_reactions extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('bbb_session_chat_reaction', true) === null) {
            $this->createTable('bbb_session_chat_reaction', [
                'id'         => $this->primaryKey(),
                'chat_id'    => $this->integer()->notNull(),
                'user_id'    => $this->integer()->notNull(),
                'emoji'      => $this->string(16)->notNull(),
                'created_at' => $this->integer()->notNull(),
            ]);

            $this->createIndex(
                'idx_bbb_scr_unique',
                'bbb_session_chat_reaction',
                ['chat_id', 'user_id', 'emoji'],
                true
            );

            $this->addForeignKey(
                'fk_bbb_scr_chat',
                'bbb_session_chat_reaction',
                'chat_id',
                'bbb_session_meeting_chat',
                'id',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        if ($this->db->getTableSchema('bbb_session_chat_reaction', true) !== null) {
            $this->dropTable('bbb_session_chat_reaction');
        }
    }
}
