<?php

use yii\db\Migration;

class m250827_112055_addPublicJoinField extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $table = $this->db->getTableSchema('bbb_session', true);

        if ($table === null || !isset($table->columns['public_join'])) {
            $this->addColumn(
                'bbb_session',
                'public_join',
                $this->boolean()->notNull()->defaultValue(false)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $table = $this->db->getTableSchema('bbb_session', true);

        if ($table !== null && isset($table->columns['public_join'])) {
            $this->dropColumn('bbb_session', 'public_join');
        }

        return true;
    }
}
