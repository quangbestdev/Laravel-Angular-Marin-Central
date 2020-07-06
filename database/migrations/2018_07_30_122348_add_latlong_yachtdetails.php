<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLatlongYachtdetails extends Migration
{
     /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('yachtdetail', function (Blueprint $table) {
             $table->decimal('longitude',20,10)->default('0.0');
        });
        Schema::table('yachtdetail', function (Blueprint $table) {
             $table->decimal('latitude',20,10)->default('0.0');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
