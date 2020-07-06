<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUsernameColumnAuthTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('auths', function (Blueprint $table) {
            $table->string('firstname_admin')->nullable();
            $table->string('lastname_admin')->nullable();
        });
        DB::statement("ALTER TABLE auths ALTER COLUMN firstname_admin SET DEFAULT NULL");
        DB::statement("ALTER TABLE auths ALTER COLUMN firstname_admin SET DEFAULT NULL");
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
