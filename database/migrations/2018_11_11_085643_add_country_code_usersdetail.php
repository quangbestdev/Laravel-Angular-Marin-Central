<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCountryCodeUsersdetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('yachtdetail', function (Blueprint $table) {
            $table->string('country_code',20)->default('+1');
        });
        Schema::table('userdetails', function (Blueprint $table) {
            $table->string('country_code',20)->default('+1');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('yachtdetail', function (Blueprint $table) {
            $table->dropColumn('country_code');
        });
        Schema::table('userdetails', function (Blueprint $table) {
            $table->dropColumn('country_code');
        });
    }
}
