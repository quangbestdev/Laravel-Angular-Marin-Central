<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCountyToUserdetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('userdetails', function (Blueprint $table) {
            $table->string('county')->nullable();
        });
        Schema::table('claimed_business', function (Blueprint $table) {
            $table->string('county')->nullable();
        });
        Schema::table('claimed_geolocation', function (Blueprint $table) {
            $table->string('county')->nullable();
        });
        Schema::table('companydetails', function (Blueprint $table) {
            $table->string('county')->nullable();
        });
        Schema::table('geolocation', function (Blueprint $table) {
            $table->string('county')->nullable();
        });
        Schema::table('talentdetails', function (Blueprint $table) {
            $table->string('county')->nullable();
        });
        Schema::table('yachtdetail', function (Blueprint $table) {
            $table->string('county')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('userdetails', function (Blueprint $table) {
            //
        });
    }
}
