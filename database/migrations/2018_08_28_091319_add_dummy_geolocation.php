<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDummyGeolocation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dummy_geolocation', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('authid');
            $table->string('city',255);
            $table->string('county')->nullable();
            $table->string('state',255);
            $table->string('country',255);
            $table->string('zipcode',100);
            $table->string('address',255)->nullable();
            $table->decimal('longitude',20,10);
            $table->decimal('latitude',20,10);
            $table->string('additional_location')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE dummy_geolocation ALTER COLUMN additional_location TYPE addedlocation  USING (additional_location::addedlocation)");
        DB::statement("ALTER TABLE dummy_geolocation ALTER COLUMN additional_location SET DEFAULT '0'");
        DB::statement('ALTER TABLE dummy_geolocation ALTER COLUMN status TYPE location_status  USING (status::location_status)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Schema::dropIfExists('dummy_geolocation');
    }
}
