<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdminNoteUpdate extends Migration
{   
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()     
    {
        Schema::table('companydetails', function (Blueprint $table) {
            $table->integer('assign_admin')->nullable();
            $table->text('admin_note')->nullable();
        });
        DB::statement("ALTER TABLE companydetails ALTER COLUMN assign_admin SET DEFAULT NULL");
        DB::statement("ALTER TABLE companydetails ALTER COLUMN admin_note SET DEFAULT NULL");
        Schema::table('dummy_registration', function (Blueprint $table) {
            $table->integer('assign_admin')->nullable();
            $table->text('admin_note')->nullable();
        });
        DB::statement("ALTER TABLE dummy_registration ALTER COLUMN assign_admin SET DEFAULT NULL");
        DB::statement("ALTER TABLE dummy_registration ALTER COLUMN admin_note SET DEFAULT NULL");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::dropIfExists('boat-engine-companies');
    }
}
