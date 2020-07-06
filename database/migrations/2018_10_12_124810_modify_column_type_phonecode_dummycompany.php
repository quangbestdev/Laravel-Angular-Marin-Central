<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyColumnTypePhonecodeDummycompany extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       
         Schema::table('companydetails', function (Blueprint $table) {
            $table->string('country_code',20)->nullable();
        });

        DB::statement("ALTER TABLE companydetails ALTER COLUMN country_code SET DEFAULT '+1'");
        Schema::table('dummy_registration', function (Blueprint $table) {
            $table->string('country_code',20)->nullable();
        });
        DB::statement("ALTER TABLE dummy_registration ALTER COLUMN country_code SET DEFAULT '+1'");

        Schema::table('country_codes', function (Blueprint $table) {
            $table->string('phonecode', 20);
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

