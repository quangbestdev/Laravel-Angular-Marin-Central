<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDefaultToIsSocial extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE auths ALTER COLUMN is_social SET DEFAULT '0'");
        DB::statement("ALTER TABLE dummy_registration ALTER COLUMN is_social SET DEFAULT '0'");
        DB::statement("ALTER TABLE rejected_registration ALTER COLUMN is_social SET DEFAULT '0'");
        DB::statement("UPDATE auths SET is_social='0'");
        DB::statement("UPDATE rejected_registration SET is_social='0'");
        DB::statement("UPDATE dummy_registration SET is_social='0'");
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
