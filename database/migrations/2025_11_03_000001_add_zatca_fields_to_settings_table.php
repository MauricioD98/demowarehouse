<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'company_name_ar')) {
                $table->string('company_name_ar')->nullable();
            }
            if (! Schema::hasColumn('settings', 'vat_number')) {
                $table->string('vat_number')->nullable();
            }
            if (! Schema::hasColumn('settings', 'zatca_enabled')) {
                $table->boolean('zatca_enabled')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'company_name_ar')) {
                $table->dropColumn('company_name_ar');
            }
            if (Schema::hasColumn('settings', 'vat_number')) {
                $table->dropColumn('vat_number');
            }
            if (Schema::hasColumn('settings', 'zatca_enabled')) {
                $table->dropColumn('zatca_enabled');
            }
        });
    }
};





