<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUpdateYachtdetailTable extends Migration
{   
    public function create_enum($name, $strings) {
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {   
        DB::statement("ALTER TABLE yachtdetail ADD COLUMN primaryimage text  DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::dropIfExists('update_companydetails');
    }
}
