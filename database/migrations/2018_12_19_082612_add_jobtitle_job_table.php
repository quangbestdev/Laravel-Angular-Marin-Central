<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddJobtitleJobTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->integer('jobtitleid')->nullable();
        });
        DB::statement("ALTER TABLE jobs ALTER COLUMN salary DROP NOT NULL");
        DB::statement("ALTER TABLE jobs ALTER COLUMN salarytype DROP NOT NULL;");
        
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
