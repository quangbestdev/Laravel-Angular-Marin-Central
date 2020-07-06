<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddContactMobileEmailToCompanydetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companydetails', function($table) {
            $table->string('contactname',255)->nullable();
            $table->string('contactmobile',255)->nullable();
            $table->string('contactemail',255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companydetails', function($table) {
            $table->dropColumn('contactname');
            $table->dropColumn('contactmobile');
            $table->dropColumn('contactemail');
        });
    }
}
