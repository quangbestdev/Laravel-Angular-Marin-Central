<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDefaultAuthemailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE auths ALTER COLUMN email SET DEFAULT NULL");
        DB::statement("ALTER TABLE dummy_registration ALTER COLUMN email SET DEFAULT NULL");
        DB::statement("ALTER TABLE rejected_registration ALTER COLUMN email SET DEFAULT NULL");
        DB::statement("ALTER TABLE auths ALTER COLUMN email  DROP NOT NULL");
        DB::statement("ALTER TABLE dummy_registration ALTER COLUMN email DROP NOT NULL");
        DB::statement("ALTER TABLE rejected_registration ALTER COLUMN email DROP NOT NULL");
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
