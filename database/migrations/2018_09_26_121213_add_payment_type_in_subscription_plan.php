<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaymentTypeInSubscriptionPlan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('paymenthistory', function (Blueprint $table) {
            $table->integer('payment_type')->nullable();
        });
        Schema::table('dummy_paymenthistory', function (Blueprint $table) {
            $table->integer('payment_type')->nullable();
        });
        Schema::table('rejected_paymenthistory', function (Blueprint $table) {
            $table->integer('payment_type')->nullable();
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
