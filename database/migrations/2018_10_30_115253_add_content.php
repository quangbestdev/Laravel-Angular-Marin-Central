<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddContent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companydetails', function (Blueprint $table) {
            $table->text('contact_content')->nullable();
		});
		Schema::table('yachtdetail', function (Blueprint $table) {
            $table->text('quote_content')->nullable();
		});
		Schema::table('talentdetails', function (Blueprint $table) {
            $table->text('quote_content')->nullable();
            $table->text('applyjob_content')->nullable();
		});
		Schema::table('userdetails', function (Blueprint $table) {
            $table->text('quote_content')->nullable();
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
