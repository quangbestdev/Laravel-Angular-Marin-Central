<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubidCompanyListing extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
		Schema::table('dummy_registration', function (Blueprint $table) {
            $table->text('subscription_id')->nullable();
		});
		Schema::table('companydetails', function (Blueprint $table) {
            $table->text('subscription_id')->nullable();
		});
		Schema::table('rejected_registration', function (Blueprint $table) {
            $table->text('subscription_id')->nullable();
		});
		Schema::table('paymenthistory', function (Blueprint $table) {
            $table->text('subscription_id')->nullable();
		});
		Schema::table('dummy_paymenthistory', function (Blueprint $table) {
            $table->text('subscription_id')->nullable();
		});
		Schema::table('rejected_paymenthistory', function (Blueprint $table) {
            $table->text('subscription_id')->nullable();
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
