<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsNotificationInMessages extends Migration
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
         Schema::table('messages', function (Blueprint $table) {
             $table->text('is_notified')->nullable();
        });
        $this->create_enum('is_notified',"'0', '1'");
        DB::statement('ALTER TABLE messages ALTER COLUMN is_notified TYPE is_notified USING (is_notified::is_notified) ');
        DB::statement("UPDATE messages set is_notified='0'");
        DB::statement("ALTER TABLE messages ALTER COLUMN is_notified SET NOT NULL");
        DB::statement("ALTER TABLE messages ALTER COLUMN is_notified SET DEFAULT '0'");
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
