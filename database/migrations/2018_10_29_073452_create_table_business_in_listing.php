<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableBusinessInListing extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_in_listing_page', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id');
            $table->timestamps();
        });
        DB::statement("CREATE VIEW business_listing_count AS select company_id, COUNT(*) as businesscount from business_in_listing_page group by company_id");
        // Business contacted by dialing number from listing pages.   
        Schema::create('business_telephone_click', function (Blueprint $table) {
             $table->increments('id');
            $table->integer('authid')->nullable();
            $table->integer('company_id');
            $table->string('ip_address',255);
            $table->timestamps();
        });
        DB::statement("CREATE VIEW business_telephone_count AS select company_id, COUNT(*) as telephonecount from business_telephone_click group by company_id");
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
