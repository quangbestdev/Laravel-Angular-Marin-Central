<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TextNotificationTalentdetail extends Migration
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
        Schema::table('talentdetails', function (Blueprint $table) {
            $table->string('text_notification')->nullable();
            $table->string('text_notification_other')->nullable();
            
        });
        $this->create_enum('text_notification',"'0', '1'");
        DB::statement("ALTER TABLE talentdetails ALTER COLUMN text_notification TYPE text_notification  USING (text_notification::text_notification)");
        DB::statement("ALTER TABLE talentdetails ALTER COLUMN text_notification SET DEFAULT '1'");
        DB::statement("ALTER TABLE talentdetails ALTER COLUMN text_notification_other TYPE text_notification  USING (text_notification_other::text_notification)");
        DB::statement("ALTER TABLE talentdetails ALTER COLUMN text_notification_other SET DEFAULT '0'");
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
