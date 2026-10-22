<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Copies accounts from the old app's user_account table, if it exists, into users.
 * Keeps the same ids and passwords; plain-text passwords are hashed on the way.
 * The old table is left untouched.
 */
final class ImportLegacyUsers extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('user_account') || (int) $this->fetchRow('SELECT COUNT(*) AS n FROM users')['n'] > 0) {
            return;
        }
        $hasRole = $this->table('user_account')->hasColumn('userType');
        $roles = ['admin' => 'admin', 'staff' => 'staff', 'employee' => 'employee', 'emoloyee' => 'employee'];
        $now = date('Y-m-d H:i:s');
        $taken = [];

        foreach ($this->fetchAll('SELECT * FROM user_account ORDER BY user_id') as $i => $old) {
            $username = trim((string) $old['username']) ?: 'user' . $old['user_id'];
            if (isset($taken[strtolower($username)])) {
                $username .= '-' . $old['user_id'];
            }
            $taken[strtolower($username)] = true;

            $password = (string) $old['user_pass'];
            $hash = password_get_info($password)['algo'] === null ? password_hash($password, PASSWORD_DEFAULT) : $password;
            $name = trim(($old['user_fname'] ?? '') . ' ' . ($old['user_lname'] ?? '')) ?: $username;
            // Without roles in the old data, the oldest account becomes the Admin
            $role = $hasRole ? ($roles[strtolower((string) $old['userType'])] ?? 'employee') : ($i === 0 ? 'admin' : 'employee');

            $this->table('users')->insert([
                'id' => (int) $old['user_id'],
                'username' => $username,
                'name' => mb_substr($name, 0, 150),
                'phone' => ($old['user_phone'] ?? '') !== '' ? (string) $old['user_phone'] : null,
                'password_hash' => $hash,
                'role' => $role,
                'is_active' => 1,
                'created_at' => $old['user_created_at'] ?? $now,
                'updated_at' => $old['user_updated_at'] ?? $now,
            ])->saveData();
        }
    }

    public function down(): void
    {
        // Imported rows are ordinary users; nothing to undo safely.
    }
}
