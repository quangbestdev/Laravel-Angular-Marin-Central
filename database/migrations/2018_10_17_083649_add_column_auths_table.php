<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnAuthsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
		Schema::table('auths', function (Blueprint $table) {
            $table->string('requested_email')->nullable();
            $table->text('email_hash')->nullable();
        });
        DB::statement("ALTER TABLE auths ALTER COLUMN requested_email SET DEFAULT NULL");
        DB::statement("ALTER TABLE auths ALTER COLUMN email_hash SET DEFAULT NULL");
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
