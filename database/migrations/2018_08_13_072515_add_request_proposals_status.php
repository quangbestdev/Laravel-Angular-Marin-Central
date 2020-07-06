<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRequestProposalsStatus extends Migration
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
        Schema::table('request_proposals', function (Blueprint $table) {
            $table->string('status');
        });
        $this->create_enum('request_status',"'active', 'pending','deleted','completed'");
        DB::statement('ALTER TABLE request_proposals ALTER COLUMN status TYPE request_status  USING (status::request_status)');
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('request_proposals', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
