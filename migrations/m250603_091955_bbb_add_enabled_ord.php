<?php

use yii\db\Migration;

class m250603_091955_bbb_add_enabled_ord extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $table = $this->db->getTableSchema('bbb_session', true);

        if ($table === null || !isset($table->columns['enabled'])) {
            $this->addColumn(
                'bbb_session',
                'enabled',
                $this->boolean()->notNull()->defaultValue(true)
            );
        }
        if ($table === null || !isset($table->columns['ord'])) {
            $this->addColumn(
                'bbb_session',
                'ord',
                $this->integer()->notNull()->defaultValue(0)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250603_091955_bbb_add_enabled_ord cannot be reverted.\n";

        return false;
    }
}
