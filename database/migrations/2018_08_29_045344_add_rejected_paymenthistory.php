<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRejectedPaymenthistory extends Migration
{
   /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rejected_paymenthistory', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('companyid');
            $table->integer('requestid')->default('0');
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
        DB::statement('ALTER TABLE rejected_paymenthistory ALTER COLUMN transactionfor TYPE transactionfor USING (transactionfor::transactionfor)');
        DB::statement('ALTER TABLE rejected_paymenthistory ALTER COLUMN status TYPE paymentstatus USING (status::paymentstatus)');
        DB::statement("ALTER TABLE auths ADD COLUMN accounttype accounttype NOT NULL DEFAULT 'real'");
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
