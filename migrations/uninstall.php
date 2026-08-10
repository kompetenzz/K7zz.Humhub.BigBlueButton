<?php

use yii\db\Migration;

class uninstall extends Migration
{
    public function up()
    {
        // FK-safe order: children before parents.
        //
        // Each table is dropped only if it actually exists. On some installations
        // the schema does not match the full table set (older version uninstalled,
        // an update that aborted mid-migration, a restored dump, ...). A plain
        // dropTable() on a missing table throws, which would abort the uninstall
        // after the first missing table – leaving the remaining tables behind while
        // HumHub wipes the whole migration history regardless (see
        // MigrationService::uninstall()). On reinstall the migrations then re-run
        // against the leftover tables and fail with "table already exists".
        $tables = [
            'bbb_session_chat_reaction',
            'bbb_session_meeting_chat',
            'bbb_session_meeting_join',
            'bbb_session_meeting',
            'bbb_recording_format',
            'bbb_session_user',
            'bbb_session',
        ];

        foreach ($tables as $table) {
            if ($this->db->getTableSchema($table, true) !== null) {
                $this->dropTable($table);
            }
        }
    }

    public function down()
    {
        echo "uninstall does not support migration down.\n";
        return false;
    }

}
