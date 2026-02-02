<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if column exists before renaming to avoid errors
        if (Schema::hasColumn('products', 'TaxNet')) {
            Schema::table('products', function (Blueprint $table) {
                $table->renameColumn('TaxNet', 'tax_net');
            });
        }

        // For PostgreSQL case: column might be lowercase already
        if (!Schema::hasColumn('products', 'tax_net') && Schema::hasColumn('products', 'taxnet')) {
            Schema::table('products', function (Blueprint $table) {
                $table->renameColumn('taxnet', 'tax_net');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('products', 'tax_net')) {
            Schema::table('products', function (Blueprint $table) {
                $table->renameColumn('tax_net', 'TaxNet');
            });
        }
    }
};
