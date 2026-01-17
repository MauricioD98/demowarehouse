<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddFiledsToProductVariants extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->float('cost', 10, 0)->nullable();
            $table->float('price', 10, 0)->nullable();
            $table->string('code', 192)->nullable();
            $table->string('image')->default('no-image.png')->nullable();
        });

        // PostgreSQL compatible UPDATE with JOIN using FROM clause
        DB::statement("
            UPDATE product_variants 
            SET 
                cost = products.cost,
                price = products.price,
                code = CONCAT(product_variants.name, '-', products.code)
            FROM products
            WHERE product_variants.product_id = products.id
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_variants', function (Blueprint $table) {
            //
        });
    }
}
