<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmailverificationTable extends Migration
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
        Schema::create('emailverification', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->integer('otp');
            $table->string('status');
        });
        $this->create_enum('otpstatus',"'0', '1'");
        DB::statement('ALTER TABLE emailverification ALTER COLUMN status TYPE otpstatus USING (status::otpstatus)');
        DB::statement("ALTER TABLE emailverification ALTER COLUMN status SET DEFAULT '1'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('emailverification');
    }
}
