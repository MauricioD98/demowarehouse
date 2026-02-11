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
        Schema::table('products', function (Blueprint $table) {

            // Warranty
            $table->integer('warranty_period')->nullable();
            $table->string('warranty_unit')->nullable(); // days|months|years
            $table->text('warranty_terms')->nullable();

            // Guarantee (if you want to track separately)
            $table->boolean('has_guarantee')->default(false);
            $table->integer('guarantee_period')->nullable();
            $table->string('guarantee_unit')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};
