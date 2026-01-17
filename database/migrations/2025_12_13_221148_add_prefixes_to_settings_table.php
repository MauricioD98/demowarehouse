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
        Schema::table('settings', function (Blueprint $table) {
            $table->string('sale_prefix', 10)->nullable()->default('SL');
            $table->string('purchase_prefix', 10)->nullable()->default('PR');
            $table->string('quotation_prefix', 10)->nullable()->default('QT');
            $table->string('adjustment_prefix', 10)->nullable()->default('AD');
            $table->string('transfer_prefix', 10)->nullable()->default('TR');
            $table->string('sale_return_prefix', 10)->nullable()->default('RT');
            $table->string('purchase_return_prefix', 10)->nullable()->default('RT');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['sale_prefix', 'purchase_prefix', 'quotation_prefix', 'adjustment_prefix', 'transfer_prefix', 'sale_return_prefix', 'purchase_return_prefix']);
        });
    }
};
