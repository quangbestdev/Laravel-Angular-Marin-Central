<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApplyJobsTable extends Migration
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
        Schema::create('apply_jobs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('jobid');
            $table->integer('authid');
            $table->string('status');
            $table->timestamps();
        });

        $this->create_enum('apply_status',"'active', 'deleted'");
        DB::statement('ALTER TABLE apply_jobs ALTER COLUMN status TYPE apply_status  USING (status::apply_status)');
        DB::statement("ALTER TABLE apply_jobs ALTER COLUMN status   SET DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('apply_jobs');
    }
}
