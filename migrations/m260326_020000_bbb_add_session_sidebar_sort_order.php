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
        $this->addColumn('bbb_session', 'sidebar_sort_order', $this->integer()->notNull()->defaultValue(1));
    }

    public function safeDown()
    {
        $this->dropColumn('bbb_session', 'sidebar_sort_order');
    }
}
