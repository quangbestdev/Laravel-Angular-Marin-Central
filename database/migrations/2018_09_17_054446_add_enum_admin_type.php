<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEnumAdminType extends Migration
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
        Schema::table('auths', function (Blueprint $table) {
            $table->json('admin_privilege')->nullable();
        });
        Schema::table('messages', function($table)
        {
            $table->dropColumn('message_type');
        });
        Schema::table('messages', function($table)
        {
            $table->string('message_type');
        });
        $this->create_enum('message_types',"'lead','request_quote','contact_now','vacancy','comment'");   DB::statement('ALTER TABLE messages ALTER COLUMN message_type TYPE message_types USING (message_type::message_types) ');
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
