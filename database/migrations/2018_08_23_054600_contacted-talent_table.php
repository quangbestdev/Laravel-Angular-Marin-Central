<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ContactedTalentTable extends Migration
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
         Schema::create('contacted_talent', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('companyid');
            $table->integer('talentid');
            $table->text('message');
            $table->string('status');
            $table->timestamps();
             });
        $this->create_enum('talent_status',"'0', '1'");
        DB::statement('ALTER TABLE contacted_talent ALTER COLUMN status TYPE talent_status  USING (status::talent_status)');
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
