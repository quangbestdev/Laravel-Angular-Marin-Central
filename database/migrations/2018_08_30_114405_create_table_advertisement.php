<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableAdvertisement extends Migration
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

        Schema::create('advertisement', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',255);
            $table->string('link',255);
            $table->text('vertical_image')->nullable();
            $table->text('horizontal_image')->nullable();
            $table->json('pages');
            $table->string('status');
            $table->timestamps();
        });
        $this->create_enum('ads_status',"'0', '1'");
         DB::statement('ALTER TABLE advertisement ALTER COLUMN status TYPE ads_status  USING (status::ads_status)');
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
