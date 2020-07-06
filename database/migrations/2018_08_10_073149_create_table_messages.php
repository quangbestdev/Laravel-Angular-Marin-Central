<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableMessages extends Migration
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
        Schema::create('messages', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('message_to');
            $table->integer('message_from');
            $table->string('subject',255);
            $table->text('message');
            $table->text('attachment')->nullable();
            $table->string('is_read');
            $table->string('is_deleted');
            $table->timestamps();
        });
        $this->create_enum('is_deleted',"'0', '1'");
        DB::statement('ALTER TABLE messages ALTER COLUMN is_deleted TYPE is_deleted  USING (is_deleted::is_deleted)');
        DB::statement('ALTER TABLE messages ALTER COLUMN is_read TYPE is_read  USING (is_read::is_read)');
        DB::statement("ALTER TABLE messages ALTER COLUMN is_read   SET DEFAULT '0'");
        DB::statement("ALTER TABLE messages ALTER COLUMN is_deleted   SET DEFAULT '0'");
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messages');
    }
}
