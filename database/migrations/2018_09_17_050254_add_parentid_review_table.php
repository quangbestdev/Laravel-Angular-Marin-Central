<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddParentidReviewTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_request_reviews', function (Blueprint $table) {
            $table->integer('parent_id')->default('0');
            //$table->integer('message_id')->default('0');
            $table->string('from_usertype')->nullable();
            $table->string('to_usertype')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
    }
}
