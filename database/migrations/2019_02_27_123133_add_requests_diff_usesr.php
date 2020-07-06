<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRequestsDiffUsesr extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users_service_requests', function($table) {
            $table->string('request_type')->default('normal_request');;
            $table->string('cityForCharter')->nullable();
            $table->string('charterDays')->nullable();
            $table->string('totalPeople')->nullable();
            $table->string('charterType')->nullable();
            $table->string('maxPrice')->nullable();
            $table->string('startDate')->nullable();
            $table->string('endDate')->nullable();
            $table->string('boatName')->nullable();
            $table->string('lengthbeam')->nullable();
            $table->string('lengthboat')->nullable();
            $table->string('lengthdraft')->nullable();
            $table->string('metricboatgroup')->nullable();
            $table->string('metricbeamgroup')->nullable();
            $table->string('metricdraftgroup')->nullable();
            $table->string('powerType')->nullable();
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
