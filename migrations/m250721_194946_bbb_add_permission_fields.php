<?php

use yii\db\Migration;

class m250721_194946_bbb_add_permission_fields extends Migration
{
    private const COLUMNS = [
        'join_can_start',
        'join_can_moderate',
        'has_waitingroom',
        'allow_recording',
        'mute_on_entry',
    ];

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $table = $this->db->getTableSchema('bbb_session', true);

        if ($table === null || !isset($table->columns['join_can_start'])) {
            $this->addColumn(
                'bbb_session',
                'join_can_start',
                $this->boolean()->notNull()->defaultValue(false)
            );
        }
        if ($table === null || !isset($table->columns['join_can_moderate'])) {
            $this->addColumn(
                'bbb_session',
                'join_can_moderate',
                $this->boolean()->notNull()->defaultValue(false)
            );
        }
        if ($table === null || !isset($table->columns['has_waitingroom'])) {
            $this->addColumn(
                'bbb_session',
                'has_waitingroom',
                $this->boolean()->notNull()->defaultValue(false)
            );
        }
        if ($table === null || !isset($table->columns['allow_recording'])) {
            $this->addColumn(
                'bbb_session',
                'allow_recording',
                $this->boolean()->notNull()->defaultValue(true)
            );
        }
        if ($table === null || !isset($table->columns['mute_on_entry'])) {
            $this->addColumn(
                'bbb_session',
                'mute_on_entry',
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
        if ($table === null) {
            return true;
        }

        foreach (self::COLUMNS as $column) {
            if (isset($table->columns[$column])) {
                $this->dropColumn('bbb_session', $column);
            }
        }

        return true;
    }
}
