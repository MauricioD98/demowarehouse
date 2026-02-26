<?php

namespace Database\Seeders;

use App\Models\Cash;
use App\Models\CashUser;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class CashDefaultSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = Warehouse::whereNull('deleted_at')->get();
        $users = User::whereNull('deleted_at')->get();

        foreach ($warehouses as $wh) {
            $exists = Cash::where('warehouse_id', $wh->id)->exists();
            if (! $exists) {
                Cash::create([
                    'code' => 'CASH-'.str_pad($wh->id, 4, '0', STR_PAD_LEFT),
                    'name' => 'Caja '.$wh->name,
                    'description' => 'Caja principal del almacén '.$wh->name,
                    'state' => 1,
                    'warehouse_id' => $wh->id,
                ]);
            }
        }

        $cashes = Cash::where('state', 1)->get();
        foreach ($cashes as $cash) {
            foreach ($users as $user) {
                $exists = CashUser::where('user_id', $user->id)->where('cash_id', $cash->id)->exists();
                if (! $exists) {
                    CashUser::create(['user_id' => $user->id, 'cash_id' => $cash->id]);
                }
            }
        }
    }
}
