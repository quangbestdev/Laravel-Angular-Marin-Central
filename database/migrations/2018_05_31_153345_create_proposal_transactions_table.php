<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProposalTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('proposal_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('jobid');
            $table->string('transactionid');
            $table->decimal('total_amount',10,2);
            $table->decimal('company_dis',10,2);
            $table->decimal('web_dis',10,2);
            $table->string('status');
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
        Schema::dropIfExists('proposal_transactions');
    }
}
