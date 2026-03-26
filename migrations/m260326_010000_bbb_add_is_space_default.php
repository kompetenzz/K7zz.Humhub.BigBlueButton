<?php

use yii\db\Migration;

/**
 * Migration: Add is_space_default flag to BBB sessions.
 *
 * Marks a session as the "space default" session.
 * When set, the sidebar widget suppresses the session title.
 */
class m260326_010000_bbb_add_is_space_default extends Migration
{
    public function safeUp()
    {
        $this->addColumn('bbb_session', 'is_space_default', $this->tinyInteger(1)->notNull()->defaultValue(0));
    }

    public function safeDown()
    {
        $this->dropColumn('bbb_session', 'is_space_default');
    }
}
