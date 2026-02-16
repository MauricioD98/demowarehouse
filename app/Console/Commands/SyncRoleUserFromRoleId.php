<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncRoleUserFromRoleId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'role_user:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync role_user table from users.role_id (ensures every user with role_id has a role_user row for permission checks)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $userIdsInPivot = DB::table('role_user')->pluck('user_id')->unique()->values()->all();
        $users = DB::table('users')
            ->whereNull('deleted_at')
            ->whereNotNull('role_id')
            ->whereNotIn('id', $userIdsInPivot ?: [0])
            ->select('id', 'role_id')
            ->get();

        if ($users->isEmpty()) {
            $this->info('All users with role_id already have a row in role_user.');

            return 0;
        }

        $now = now();
        $count = 0;
        foreach ($users as $user) {
            DB::table('role_user')->updateOrInsert(
                ['user_id' => $user->id],
                ['role_id' => $user->role_id, 'created_at' => $now, 'updated_at' => $now]
            );
            $count++;
        }

        $this->info("Synced {$count} user(s) into role_user.");

        return 0;
    }
}
