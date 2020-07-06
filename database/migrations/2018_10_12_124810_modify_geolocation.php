<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyGeolocation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dummy_geolocation', function (Blueprint $table) {
            $table->dropColumn('address');
            $table->dropColumn('additional_location');
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
            $table->dropColumn('country');
        });
        Schema::table('geolocation', function (Blueprint $table) {
            $table->dropColumn('address');
            $table->dropColumn('additional_location');
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
            $table->dropColumn('country');
        });
        Schema::table('rejected_geolocation', function (Blueprint $table) {
            $table->dropColumn('address');
            $table->dropColumn('additional_location');
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
            $table->dropColumn('country');
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

