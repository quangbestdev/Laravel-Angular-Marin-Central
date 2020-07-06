<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateGeolocationTable extends Migration
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
        Schema::table('geolocation', function($table)
        {
            $table->string('additional_location')->nullable();
        });
        $this->create_enum('addedlocation',"'0', '1'");
        DB::statement("ALTER TABLE geolocation ALTER COLUMN additional_location TYPE addedlocation  USING (additional_location::addedlocation)");
        DB::statement("ALTER TABLE geolocation ALTER COLUMN additional_location SET DEFAULT '0'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('geolocation', function ($table) {
            $table->dropColumn('additional_location');
        });
    }
}
