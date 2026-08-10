<?php

use yii\db\Migration;

/**
 * Migration: Add show_in_sidebar flag to BBB sessions.
 *
 * Allows individual sessions to be displayed in the space right-column sidebar widget.
 */
class m260326_000000_bbb_add_show_in_sidebar extends Migration
{
    public function safeUp()
    {
        $table = $this->db->getTableSchema('bbb_session', true);

        if ($table === null || !isset($table->columns['show_in_sidebar'])) {
            $this->addColumn('bbb_session', 'show_in_sidebar', $this->tinyInteger(1)->notNull()->defaultValue(0));
        }
    }

    public function safeDown()
    {
        $table = $this->db->getTableSchema('bbb_session', true);

        if ($table !== null && isset($table->columns['show_in_sidebar'])) {
            $this->dropColumn('bbb_session', 'show_in_sidebar');
        }
    }
}
