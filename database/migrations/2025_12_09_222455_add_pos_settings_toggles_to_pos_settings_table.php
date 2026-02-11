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
        Schema::table('pos_settings', function (Blueprint $table) {
            $table->boolean('quick_add_customer')->default(1);
            $table->boolean('barcode_scanning_sound')->default(1);
            $table->boolean('show_product_images')->default(1);
            $table->boolean('show_stock_quantity')->default(1);
            $table->boolean('enable_hold_sales')->default(1);
            $table->boolean('enable_customer_points')->default(1);
            $table->boolean('show_categories')->default(1);
            $table->boolean('show_brands')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_settings', function (Blueprint $table) {
            $table->dropColumn([
                'quick_add_customer', 
                'barcode_scanning_sound', 
                'show_product_images',
                'show_stock_quantity',
                'enable_hold_sales',
                'enable_customer_points',
                'show_categories',
                'show_brands'
            ]);
        });
    }
};
