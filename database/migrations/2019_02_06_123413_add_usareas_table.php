<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUsareasTable extends Migration
{  
   
    public function create_enum($name, $strings) {
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = '" . $name ."') THEN
                CREATE TYPE " .  $name . " AS ENUM
                (
                    " . $strings . "
                );
            END IF;
        END$$;");
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()     
    {
        Schema::dropIfExists('newusareas');
        Schema::create('newusareas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('zipcode',100);
            $table->string('city',255);
            $table->string('state',255);
            $table->string('statename',255);
            $table->string('status');
            $table->string('county')->nullable();
        });
        DB::statement('ALTER TABLE newusareas ALTER COLUMN status TYPE usareas_status  USING (status::usareas_status)');
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
