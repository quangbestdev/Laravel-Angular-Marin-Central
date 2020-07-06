<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobsTable extends Migration
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
        Schema::create('jobs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('authid');
            $table->json('services');
            $table->string('title',255);
            $table->text('description');
            $table->integer('geolocation');
            $table->decimal('salary',10,2);
            $table->string('salarytype');
            $table->string('status');
            $table->string('request_uniqueid',255);
            $table->timestamps();
        });
        $this->create_enum('salarytype',"'hour', 'year'");
        $this->create_enum('job_status',"'0', '1'");
        DB::statement('ALTER TABLE jobs ALTER COLUMN status TYPE job_status  USING (status::job_status)');
        DB::statement('ALTER TABLE jobs ALTER COLUMN salarytype TYPE salarytype USING (salarytype::salarytype)');
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jobs');
    }
}
