<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyColumnTypePhonecodeCompanydetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('country_codes', function (Blueprint $table) {
            $table->dropColumn('phonecode');
        });
        Schema::table('companydetails', function (Blueprint $table) {
            $table->dropColumn('country_code');
        });
        Schema::table('dummy_registration', function (Blueprint $table) {
            $table->dropColumn('country_code');
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
