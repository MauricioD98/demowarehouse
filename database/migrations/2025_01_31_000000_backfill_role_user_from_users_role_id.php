<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BackfillRoleUserFromUsersRoleId extends Migration
{
    /**
     * Ensure every user with role_id has a row in role_user (required for permission checks).
     *
     * @return void
     */
    public function up()
    {
        $now = now()->format('Y-m-d H:i:s');
        $users = DB::table('users')
            ->whereNull('deleted_at')
            ->whereNotNull('role_id')
            ->whereNotIn('id', function ($q) {
                $q->select('user_id')->from('role_user');
            })
            ->select('id', 'role_id')
            ->get();

        foreach ($users as $user) {
            DB::table('role_user')->insert([
                'user_id' => $user->id,
                'role_id' => $user->role_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No safe reverse: we don't know which rows were inserted by this migration
    }
}
