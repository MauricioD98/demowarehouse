<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddTypeToProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('type')->nullable();
        });
        
        // Set the value of the "type" column based on the value of the "is_variant" column
        // PostgreSQL uses CASE WHEN instead of IF(), and boolean comparison is different
        DB::statement("
            UPDATE products 
            SET type = CASE 
                WHEN is_variant = true THEN 'is_variant' 
                ELSE 'is_single' 
            END
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
}
