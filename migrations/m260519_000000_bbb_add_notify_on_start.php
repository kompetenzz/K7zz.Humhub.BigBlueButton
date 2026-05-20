<?php

use yii\db\Migration;

class m260519_000000_bbb_add_notify_on_start extends Migration
{
    public function safeUp()
    {
        $this->addColumn('bbb_session', 'notify_on_start', $this->boolean()->notNull()->defaultValue(true));
    }

    public function safeDown()
    {
        $this->dropColumn('bbb_session', 'notify_on_start');
    }
}
