<?php

use yii\db\Migration;

class m260512_020000_bbb_add_start_presentation_hidden extends Migration
{
    public function safeUp()
    {
        $this->addColumn('bbb_session', 'start_presentation_hidden', $this->boolean()->notNull()->defaultValue(false));
    }

    public function safeDown()
    {
        $this->dropColumn('bbb_session', 'start_presentation_hidden');
    }
}
