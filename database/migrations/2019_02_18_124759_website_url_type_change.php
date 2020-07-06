<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class WebsiteUrlTypeChange extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE dummy_registration ALTER COLUMN websiteurl TYPE text");
        DB::statement("ALTER TABLE dummy_registration_backup ALTER COLUMN websiteurl TYPE text");
        DB::statement("ALTER TABLE rejected_registration ALTER COLUMN websiteurl TYPE text");
        DB::statement("ALTER TABLE claimed_business ALTER COLUMN websiteurl TYPE text");
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
