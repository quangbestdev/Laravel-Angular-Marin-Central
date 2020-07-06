<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldBoatengineDummy extends Migration
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
        Schema::table('dummy_registration', function (Blueprint $table) {
            $table->json('boats_yachts_worked')->nullable();
            $table->json('engines_worked')->nullable();
        });
        DB::statement("ALTER TABLE dummy_registration ALTER COLUMN boats_yachts_worked SET DEFAULT NULL");
        DB::statement("ALTER TABLE dummy_registration ALTER COLUMN engines_worked SET DEFAULT NULL");
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
