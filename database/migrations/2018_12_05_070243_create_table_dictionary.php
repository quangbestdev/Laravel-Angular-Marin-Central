<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableDictionary extends Migration
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
        Schema::create('dictionary', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('authid')->nullable();
            $table->text('word');
            $table->string('status');
        });
        $this->create_enum('dictionary_status',"'0', '1'");
        DB::statement('ALTER TABLE dictionary ALTER COLUMN status TYPE dictionary_status  USING (status::dictionary_status)');
        DB::statement("ALTER TABLE dictionary ALTER COLUMN status SET DEFAULT '1'");
        DB::statement("ALTER TABLE dictionary ALTER COLUMN authid SET DEFAULT '0'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::dropIfExists('dictionary');
    }
}
