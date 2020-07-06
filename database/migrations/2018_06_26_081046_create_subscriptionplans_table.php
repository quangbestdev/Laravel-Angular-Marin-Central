<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionplansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscriptionplans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('planname',255);
            $table->text('plandescription');
            $table->decimal('amount',10,2);
            $table->integer('resumeaccess');
            $table->integer('leadaccess');
            $table->integer('geolocationaccess');
            $table->string('plantype');
            $table->integer('planaccessnumber');
            $table->string('planaccesstype');
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
        Schema::dropIfExists('subscriptionplans');
    }
}
