<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserdetailCover extends Migration
{
     /* Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('userdetails', function (Blueprint $table) {
             $table->string('coverphoto',255)->nullable();
        });
        Schema::table('talentdetails', function (Blueprint $table) {
             $table->string('coverphoto',255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::table('userdetails', function (Blueprint $table) {
        //      $table->dropColumn('coverphoto');
        // });
    }
}
