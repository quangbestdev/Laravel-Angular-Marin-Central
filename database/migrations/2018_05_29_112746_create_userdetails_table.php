<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserdetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('userdetails', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('authid');
            $table->string('firstname',255);
            $table->string('lastname',255)->nullable();
            $table->string('sex')->nullable();
            $table->string('city',255);
            $table->string('state',255);
            $table->string('country',255);
            $table->string('zipcode');
            $table->text('address')->nullable();
            $table->string('mobile',255);
            $table->string('birthdate',255)->nullable();
            $table->string('profile_image')->nullable();
            $table->decimal('longitude',20,10);
            $table->decimal('latitude',20,10);
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
        Schema::dropIfExists('userdetails');
    }
}
