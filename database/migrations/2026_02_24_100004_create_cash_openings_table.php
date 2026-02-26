<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_opening', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cash_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->integer('cash_close_number')->default(1);
            $table->decimal('total_sale', 20, 2)->default(0);
            $table->decimal('opening_amount', 20, 2);
            $table->decimal('total_outflow', 20, 2)->default(0);
            $table->decimal('total_inflow', 20, 2)->default(0);
            $table->decimal('closing_amount', 20, 2)->nullable();
            $table->decimal('total_cash', 20, 2)->nullable();
            $table->dateTime('opening_date');
            $table->dateTime('closing_date')->nullable();
            $table->dateTime('register_date');
            $table->date('modification_date');
            $table->smallInteger('cash_state')->default(1);
            $table->smallInteger('state')->default(1);
            $table->unsignedBigInteger('opening_user_id');
            $table->unsignedBigInteger('closing_user_id')->nullable();
            $table->foreign('cash_id')->references('id')->on('cash')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('opening_user_id')->references('id')->on('users');
            $table->foreign('closing_user_id')->references('id')->on('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_opening');
    }
};
