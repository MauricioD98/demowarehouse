<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_outflow', function (Blueprint $table) {
            $table->id();
            $table->integer('outflow_num')->default(1);
            $table->date('date');
            $table->dateTime('register_date');
            $table->dateTime('modification_date');
            $table->text('concept');
            $table->decimal('total_amount', 20, 2);
            $table->string('record_type', 10)->nullable();
            $table->smallInteger('state')->default(1);
            $table->unsignedBigInteger('type_cash_outflow_id');
            $table->unsignedBigInteger('cash_opening_id');
            $table->unsignedBigInteger('cash_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('user_id');
            $table->foreign('type_cash_outflow_id')->references('id')->on('type_cash_outflow');
            $table->foreign('cash_opening_id')->references('id')->on('cash_opening');
            $table->foreign('cash_id')->references('id')->on('cash');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_outflow');
    }
};
