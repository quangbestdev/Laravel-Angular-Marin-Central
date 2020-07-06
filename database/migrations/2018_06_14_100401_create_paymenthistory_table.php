<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymenthistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paymenthistory', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('companyid');
            $table->integer('jobid')->default('0');
            $table->integer('talentid')->default('0');
            $table->text('transactionid')->nullable();
            $table->text('tokenused')->nullable();
            $table->string('transactionfor')->nullable();
            $table->decimal('amount',10,2);
            $table->string('status');
            $table->text('fingerprintid')->nullable();
            $table->text('cardid')->nullable();
            $table->timestamp('expiredate');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('paymenthistory');
    }
}
