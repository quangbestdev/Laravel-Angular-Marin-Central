<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDateDmmyregistration extends Migration
{   
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()     
    {
        Schema::table('dummy_registration', function (Blueprint $table) {
           $table->timestamps();
        });
        $DateTime = date('Y-m-d H:i:s');
        DB::statement("UPDATE dummy_registration SET created_at='".$DateTime."'");
        DB::statement("UPDATE dummy_registration SET updated_at='".$DateTime."'");
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
