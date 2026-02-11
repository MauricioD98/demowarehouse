<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero, eliminar registros duplicados manteniendo solo el primero (menor ID)
        // Esto es necesario antes de agregar la restricción única
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('
                DELETE FROM permission_role pr1
                WHERE pr1.id NOT IN (
                    SELECT MIN(pr2.id)
                    FROM permission_role pr2
                    GROUP BY pr2.permission_id, pr2.role_id
                )
            ');
        } else {
            // Para MySQL/MariaDB
            DB::statement('
                DELETE pr1 FROM permission_role pr1
                INNER JOIN permission_role pr2
                WHERE pr1.id > pr2.id
                AND pr1.permission_id = pr2.permission_id
                AND pr1.role_id = pr2.role_id
            ');
        }

        // Agregar restricción única en (permission_id, role_id)
        Schema::table('permission_role', function (Blueprint $table) {
            $table->unique(['permission_id', 'role_id'], 'permission_role_unique');
        });

        // Sincronizar la secuencia de PostgreSQL si es necesario
        if (DB::getDriverName() === 'pgsql') {
            $maxId = DB::table('permission_role')->max('id') ?? 0;
            DB::statement("SELECT setval('permission_role_id_seq', GREATEST((SELECT MAX(id) FROM permission_role), 1), true)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permission_role', function (Blueprint $table) {
            $table->dropUnique('permission_role_unique');
        });
    }
};
