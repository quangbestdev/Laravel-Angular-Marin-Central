<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobStatusJobsTable extends Migration
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
       $this->create_enum('jobliststatus',"'active', 'expired','deleted'");
       $this->create_enum('requeststatus',"'posted', 'received_leads','completed','deleted'");
       $this->create_enum('proposal_status',"'active', 'pending','rejected','declined','completed','deleted'");
        
       DB::statement("ALTER TABLE jobs ADD COLUMN status jobliststatus NOT NULL DEFAULT 'active'");
       DB::statement("ALTER TABLE users_service_requests ADD COLUMN status requeststatus NOT NULL DEFAULT 'posted'");
       DB::statement("ALTER TABLE request_proposals ADD COLUMN status proposal_status NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('jobs', function (Blueprint $table) {
            //
        });
    }

}
