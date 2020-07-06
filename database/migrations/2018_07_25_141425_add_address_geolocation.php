<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAddressGeolocation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('geolocation', function (Blueprint $table) {
             $table->string('address',255)->nullable();
        });
        Schema::table('service_request_reviews', function (Blueprint $table) {
             $table->string('subject',255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {   
        Schema::table('geolocation', function (Blueprint $table) {
            $table->dropTimestamps('address');
        });
        Schema::table('service_request_reviews', function (Blueprint $table) {
             $table->dropTimestamps('subject',255);
        });
    }
}
