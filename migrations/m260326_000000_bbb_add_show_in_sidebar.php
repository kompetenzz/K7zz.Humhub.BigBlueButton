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
        $this->addColumn('bbb_session', 'show_in_sidebar', $this->tinyInteger(1)->notNull()->defaultValue(0));
    }

    public function safeDown()
    {
        $this->dropColumn('bbb_session', 'show_in_sidebar');
    }
}
