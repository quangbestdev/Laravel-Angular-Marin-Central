<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTimestampToReviewTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_request_reviews', function (Blueprint $table) {
             $table->timestamps();
        });
        DB::statement('ALTER TABLE users_service_requests ALTER COLUMN description TYPE TEXT');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_request_reviews', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
}
