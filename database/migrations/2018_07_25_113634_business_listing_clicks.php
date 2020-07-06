<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BusinessListingClicks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_listing_clicks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('authid')->nullable();
            $table->integer('company_id');
            $table->string('ip_address',255);
            $table->timestamps();
        });
        DB::statement("CREATE OR REPLACE VIEW business_listing_clicks_view AS select COUNT(*) as totalclicks,company_id from business_listing_clicks GROUP BY company_id");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_listing_clicks');
    }
}
