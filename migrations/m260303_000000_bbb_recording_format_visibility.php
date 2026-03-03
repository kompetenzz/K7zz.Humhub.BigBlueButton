<?php

use yii\db\Migration;

/**
 * Migration: Per-format visibility control for BBB recordings.
 *
 * Creates bbb_recording_format to track which playback formats
 * are published. Formats not present in this table are treated as
 * unpublished (default).
 */
class m260303_000000_bbb_recording_format_visibility extends Migration
{
    public function safeUp()
    {
        $this->createTable('bbb_recording_format', [
            'record_id'   => $this->string(255)->notNull(),
            'format_type' => $this->string(64)->notNull(),
            'published'   => $this->tinyInteger(1)->notNull()->defaultValue(0),
        ]);
        $this->addPrimaryKey('pk_bbb_recording_format', 'bbb_recording_format', ['record_id', 'format_type']);
    }

    public function safeDown()
    {
        $this->dropTable('bbb_recording_format');
    }
}
