<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusToDummyRegistration extends Migration
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
            $table->string('status')->nullable();
            $table->integer('rejected_id')->default('0');
        });\
        Schema::table('companydetails', function (Blueprint $table) {
            $table->integer('approval_id')->default('0');
        });
        $this->create_enum('dummy_registration_status',"'pending', 'active', 'rejected'");
        DB::statement('ALTER TABLE dummy_registration ALTER COLUMN status TYPE dummy_registration_status  USING (status::dummy_registration_status)');
        DB::statement("ALTER TABLE dummy_registration ALTER COLUMN status SET DEFAULT 'pending'");
       
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
