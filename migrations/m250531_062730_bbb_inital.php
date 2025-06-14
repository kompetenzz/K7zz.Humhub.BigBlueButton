<?php

use yii\db\Migration;

/**
 * Migration to create the initial database tables for the BigBlueButton module.
 * This includes the main session table and a pivot table for user permissions.
 */

class m250531_062730_bbb_inital extends Migration
{
    /**
     * {@inheritdoc}
     */
    /*
    public function safeUp()
    {

    }
    */

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250531_062730_bbb_inital cannot be reverted.\n";

        return false;
    }

    // Use up()/down() to run migration code without a transaction.
    public function up()
    {
        // Haupttabelle für Session‑Definitionen
        $this->createTable('bbb_session', [
            'id' => $this->primaryKey(),
            'uuid' => $this->string()->notNull()->unique(), // SessionID auf BBB
            'name' => $this->string()->notNull(),
            'title' => $this->string()->null(), // optional, für Space/User
            'description' => $this->text()->null(), // optional, für Space/User
            'moderator_pw' => $this->string()->notNull(),
            'attendee_pw' => $this->string()->notNull(),
            'contentcontainer_id' => $this->integer()->null(), // Space/User – optional
            'creator_user_id' => $this->integer()->notNull(), // HumHub‑User
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'deleted_at' => $this->integer()->null(),
        ]);
        // Indizes & Constraints
        $this->createIndex('idx_bbb_session_container', 'bbb_session', 'contentcontainer_id');
        $this->addForeignKey('fk_bbb_session_container', 'bbb_session', 'contentcontainer_id', 'contentcontainer', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk_bbb_session_creator', 'bbb_session', 'creator_user_id', 'user', 'id', 'CASCADE');

        // Pivot‑Tabelle für Benutzer‑Rechte je Session
        $this->createTable('bbb_session_user', [
            'id' => $this->primaryKey(),
            'session_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'role' => "ENUM('moderator','attendee') NOT NULL DEFAULT 'attendee'",
            'can_start' => $this->boolean()->notNull()->defaultValue(false),
            'can_join' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->integer()->notNull(),
        ]);
        $this->addForeignKey('fk_bbb_mu_session', 'bbb_session_user', 'session_id', 'bbb_session', 'id', 'CASCADE');
        $this->addForeignKey('fk_bbb_mu_user', 'bbb_session_user', 'user_id', 'user', 'id', 'CASCADE');
        $this->createIndex('idx_bbb_mu_user_session', 'bbb_session_user', ['session_id', 'user_id'], true);

    }

    public function down()
    {
        echo "m250531_062730_bbb_inital cannot be reverted.\n";

        return false;
    }
}
