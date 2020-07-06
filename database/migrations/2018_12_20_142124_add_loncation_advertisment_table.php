<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLoncationAdvertismentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('advertisement', function($table)
        {
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('zipcode')->nullable();
            $table->string('state')->nullable();
            $table->decimal('longitude',20,10)->nullable();
            $table->decimal('latitude',20,10)->nullable();
            $table->json('service')->nullable();
            $table->string('address')->nullable();
        });
        DB::statement("UPDATE  advertisement SET city='Palm Beach',country='United States',state='United States',zipcode='33480',latitude='26.705971',longitude=' -80.034424' ");
        DB::statement("ALTER TABLE advertisement ALTER COLUMN city SET NOT NULL");
        DB::statement("ALTER TABLE advertisement ALTER COLUMN zipcode SET NOT NULL");
        DB::statement("ALTER TABLE advertisement ALTER COLUMN country SET NOT NULL");
        DB::statement("ALTER TABLE advertisement ALTER COLUMN state SET NOT NULL");
        DB::statement("ALTER TABLE advertisement ALTER COLUMN longitude SET NOT NULL");
        DB::statement("ALTER TABLE advertisement ALTER COLUMN latitude SET NOT NULL");
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
