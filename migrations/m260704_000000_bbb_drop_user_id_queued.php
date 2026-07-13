<?php

use yii\db\Migration;

class m260704_000000_bbb_drop_user_id_queued extends Migration
{
    public function safeUp()
    {
        $this->dropColumn('bbb_session_meeting_chat', 'user_id_queued');
    }

    public function safeDown()
    {
        $this->addColumn('bbb_session_meeting_chat', 'user_id_queued', $this->integer()->null()->after('session_id'));
    }
}
