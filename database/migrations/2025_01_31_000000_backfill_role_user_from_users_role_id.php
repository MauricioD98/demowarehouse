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
        $userIdsInPivot = DB::table('role_user')->pluck('user_id')->unique()->values()->all();
        $users = DB::table('users')
            ->whereNull('deleted_at')
            ->whereNotNull('role_id')
            ->whereNotIn('id', $userIdsInPivot ?: [0])
            ->select('id', 'role_id')
            ->get();

        $now = now()->format('Y-m-d H:i:s');
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
