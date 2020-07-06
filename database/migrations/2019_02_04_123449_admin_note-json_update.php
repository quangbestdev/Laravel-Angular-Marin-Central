<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdminNoteJsonUpdate extends Migration
{   
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()     
    {
        Schema::table('companydetails', function (Blueprint $table) {
            $table->dropColumn('admin_note');
        });
        Schema::table('dummy_registration', function (Blueprint $table) {
            $table->dropColumn('admin_note');
        });
        Schema::table('companydetails', function (Blueprint $table) {
           $table->json('admin_note')->nullable();
        });
        DB::statement("ALTER TABLE companydetails ALTER COLUMN admin_note SET DEFAULT NULL");
        Schema::table('dummy_registration', function (Blueprint $table) {
            $table->json('admin_note')->nullable();
        });
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
