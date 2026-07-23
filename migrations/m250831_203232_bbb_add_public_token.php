<?php

use yii\db\Migration;

class m250831_203232_bbb_add_public_token extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $table = $this->db->getTableSchema('bbb_session', true);

        if ($table === null || !isset($table->columns['public_token'])) {
            $this->addColumn(
                'bbb_session',
                'public_token',
                $this->string(64)->null()->unique()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $table = $this->db->getTableSchema('bbb_session', true);

        if ($table !== null && isset($table->columns['public_token'])) {
            $this->dropColumn('bbb_session', 'public_token');
        }

        return true;
    }
}
