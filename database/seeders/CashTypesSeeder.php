<?php

namespace Database\Seeders;

use App\Models\TypeCashInflow;
use App\Models\TypeCashOutflow;
use Illuminate\Database\Seeder;

class CashTypesSeeder extends Seeder
{
    public function run(): void
    {
        TypeCashInflow::firstOrCreate(
            ['name' => 'Ventas'],
            ['description' => 'Ingresos por ventas', 'state' => 1]
        );
        TypeCashInflow::firstOrCreate(
            ['name' => 'Otros ingresos'],
            ['description' => 'Otros tipos de ingresos', 'state' => 1]
        );

        TypeCashOutflow::firstOrCreate(
            ['name' => 'Gastos'],
            ['description' => 'Egresos por gastos', 'state' => 1]
        );
        TypeCashOutflow::firstOrCreate(
            ['name' => 'Otros egresos'],
            ['description' => 'Otros tipos de egresos', 'state' => 1]
        );
    }
}
