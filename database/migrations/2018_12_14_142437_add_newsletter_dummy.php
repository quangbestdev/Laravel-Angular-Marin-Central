<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewsletterDummy extends Migration
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
            $table->string('newsletter')->nullable();
        });
        $this->create_enum('newsletter',"'0', '1'");
        DB::statement("ALTER TABLE dummy_registration ALTER COLUMN newsletter TYPE newsletter  USING (newsletter::newsletter)");
        DB::statement("ALTER TABLE dummy_registration ALTER COLUMN newsletter SET DEFAULT '0'");
        DB::statement("UPDATE dummy_registration SET newsletter='0'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('dummy_registration', function (Blueprint $table) {
            $table->dropColumn('newsletter');
        });
    }
}
