<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAdsFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('advertisement', function (Blueprint $table) {
            $table->json('selectedcity')->nullable();
            $table->json('selectedzipcode')->nullable();
            $table->json('selectedstate')->nullable(); 
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable(); 
            $table->json('keywords')->nullable(); 

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
