<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Ensures admin (user_id=1) has role_id=1 in role_user. Also syncs role_user
     * from users.role_id for all users so permission checks work (e.g. PostgreSQL).
     *
     * @return void
     */
    public function run()
    {
        $now = now();
        DB::table('role_user')->updateOrInsert(
            ['user_id' => 1, 'role_id' => 1],
            ['created_at' => $now, 'updated_at' => $now]
        );

        $this->syncRoleUserFromUsersTable();
    }

    /**
     * Ensure every user with role_id has a row in role_user (required for getEffectiveRoles / policies).
     */
    private function syncRoleUserFromUsersTable(): void
    {
        $userIdsInPivot = DB::table('role_user')->pluck('user_id')->unique()->values()->all();
        $users = DB::table('users')
            ->whereNull('deleted_at')
            ->whereNotNull('role_id')
            ->whereNotIn('id', $userIdsInPivot ?: [0])
            ->select('id', 'role_id')
            ->get();

        $now = now();
        foreach ($users as $user) {
            DB::table('role_user')->updateOrInsert(
                ['user_id' => $user->id],
                ['role_id' => $user->role_id, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }
}
