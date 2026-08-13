<?php

use yii\db\Migration;

/**
 * Renames the Admin permission to ManageSession.
 *
 * Permission states are stored under the fully qualified class name in
 * group_permission and contentcontainer_permission, so existing entries
 * must be rewritten to keep configured states across the rename.
 */
class m260716_000000_bbb_rename_admin_permission extends Migration
{
    private const OLD_CLASS = 'k7zz\humhub\bbb\permissions\Admin';
    private const NEW_CLASS = 'k7zz\humhub\bbb\permissions\ManageSession';

    public function safeUp()
    {
        $this->renamePermission(self::OLD_CLASS, self::NEW_CLASS);
    }

    public function safeDown()
    {
        $this->renamePermission(self::NEW_CLASS, self::OLD_CLASS);
    }

    private function renamePermission(string $from, string $to): void
    {
        foreach (['group_permission', 'contentcontainer_permission'] as $table) {
            $this->update(
                $table,
                ['permission_id' => $to, 'class' => $to],
                ['permission_id' => $from, 'module_id' => 'bbb'],
            );
        }
    }
}
