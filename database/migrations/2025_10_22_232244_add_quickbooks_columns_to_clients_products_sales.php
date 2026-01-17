<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clients') && ! Schema::hasColumn('clients', 'quickbooks_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('quickbooks_id', 64)->nullable()->index();
            });
        }

        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'quickbooks_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('quickbooks_id', 64)->nullable()->index();
            });
        }

    }

    public function down(): void
    {
        if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'quickbooks_id')) {
            Schema::table('clients', fn (Blueprint $t) => $t->dropColumn('quickbooks_id'));
        }
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'quickbooks_id')) {
            Schema::table('products', fn (Blueprint $t) => $t->dropColumn('quickbooks_id'));
        }

    }
};
