<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NotificationsErrorLogs extends Migration
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
        Schema::create('notifications_error_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('notification_from',255)->nullable();
            $table->string('notification_to',255);
            $table->string('errortype');
            $table->string('notification_type')->nullable();
            $table->text('messages');
            $table->timestamps();
        });
        $this->create_enum('errortype',"'sms', 'email'");
        $this->create_enum('notification_type',"'service_request', 'review','job'");
        DB::statement('ALTER TABLE notifications_error_logs ALTER COLUMN errortype TYPE errortype  USING (errortype::errortype)');
        DB::statement('ALTER TABLE notifications_error_logs ALTER COLUMN notification_type TYPE notification_type  USING (notification_type::notification_type)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications_error_logs');
    }
}
