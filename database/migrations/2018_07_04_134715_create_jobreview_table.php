<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobreviewTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobreviews', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('fromid');
            $table->integer('toid');
            $table->integer('rating')->nullable();
            $table->text('comment')->nullable();
            $table->string('isdeleted');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jobreviews');
    }
}
