<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApproachedtalentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('approachedtalents', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('companyid');
            $table->integer('talentid');
            $table->text('requestedon');
            $table->string('paymentstatus');
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
        Schema::dropIfExists('approachedtalents');
    }
}
