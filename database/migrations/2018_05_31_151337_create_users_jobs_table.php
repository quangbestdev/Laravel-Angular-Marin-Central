<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_jobs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('authid');
            $table->json('services');
            $table->string('description');
            $table->decimal('budget',10,2);
            $table->string('status');
            $table->integer('numberofleads');
            $table->string('city',255);
            $table->string('state',255);
            $table->string('country',255);
            $table->string('zipcode');
            $table->text('address');
            $table->decimal('longitude',20,10);
            $table->decimal('latitude',20,10);
            $table->string('image')->nullable();
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
        Schema::dropIfExists('users_jobs');
    }
}
