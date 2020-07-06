<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SetReminderMessage extends Migration
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
            $table->string('first_alert')->nullable();
            $table->string('second_alert')->nullable();
        });
        $this->create_enum('email_reminder',"'0', '1'");
        DB::statement("ALTER TABLE messages ALTER COLUMN first_alert TYPE email_reminder  USING (first_alert::email_reminder)");
        DB::statement("ALTER TABLE messages ALTER COLUMN second_alert TYPE email_reminder  USING (second_alert::email_reminder)");
		DB::statement("ALTER TABLE messages ALTER COLUMN first_alert SET DEFAULT '0'");
        DB::statement("UPDATE messages SET first_alert='0'");
        DB::statement("ALTER TABLE messages ALTER COLUMN second_alert SET DEFAULT '0'");
        DB::statement("UPDATE messages SET second_alert='0'");
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
