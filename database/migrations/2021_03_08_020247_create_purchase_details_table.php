<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_details', function (Blueprint $table) {
            $table->id();
            $table->float('cost', 10, 0);
            $table->float('tax_net', 10, 0)->nullable()->default(0);
            $table->string('tax_method', 192)->nullable()->default('1');
            $table->float('discount', 10, 0)->nullable()->default(0);
            $table->string('discount_method', 192)->nullable()->default('1');
            $table->integer('purchase_id');
            $table->integer('product_id');
            $table->integer('product_variant_id')->nullable();
            $table->float('total', 10, 0);
            $table->float('quantity', 10, 0);
            $table->timestamps(6);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('purchase_details');
    }
}
