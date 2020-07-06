<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ProfessionalListingPages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('professional_listing_pages', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('authid')->nullable();
            $table->integer('professional_id');
            $table->string('ip_address',255);
            $table->timestamps();
        });
        DB::statement("CREATE OR REPLACE VIEW professional_listing_pages_view AS select COUNT(*) as totalclicks,professional_id from professional_listing_pages GROUP BY professional_id");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('professional_listing_pages');
    }
}
