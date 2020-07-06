<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class JobsListingPages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs_listing_pages', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('authid')->nullable();
            $table->integer('job_id');
            $table->string('ip_address',255);
            $table->timestamps();
        });
        DB::statement("CREATE OR REPLACE VIEW jobs_listing_pages_view AS select COUNT(*) as totalclicks,job_id from jobs_listing_pages GROUP BY job_id");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jobs_listing_pages');
    }
}
