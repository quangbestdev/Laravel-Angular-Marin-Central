<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateYachtdetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::create('yachtdetail', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('authid');
            $table->string('firstname',255);
            $table->string('lastname',255)->nullable();             
            $table->string('contact',255);             
            $table->text('address')->nullable();
            $table->string('city',255);
            $table->string('state',255);
            $table->string('country',255);
            $table->string('zipcode',100);
            $table->json('yachtdetail')->nullable();
            $table->string('homeport',255)->nullable();           
            $table->json('images')->nullable();
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
        Schema::dropIfExists('yachtdetail');
    }
}
