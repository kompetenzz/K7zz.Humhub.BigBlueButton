<?php

use yii\db\Migration;

/**
 * Migration: Add sidebar_sort_order to BBB sessions.
 *
 * Controls the display order of sessions within the BBB sidebar widget.
 * Lower values appear higher up. Default: 1.
 */
class m260326_020000_bbb_add_session_sidebar_sort_order extends Migration
{
    public function safeUp()
    {
        $table = $this->db->getTableSchema('bbb_session', true);

        if ($table === null || !isset($table->columns['sidebar_sort_order'])) {
            $this->addColumn('bbb_session', 'sidebar_sort_order', $this->integer()->notNull()->defaultValue(1));
        }
    }

    public function safeDown()
    {
        $table = $this->db->getTableSchema('bbb_session', true);

        if ($table !== null && isset($table->columns['sidebar_sort_order'])) {
            $this->dropColumn('bbb_session', 'sidebar_sort_order');
        }
    }
}
