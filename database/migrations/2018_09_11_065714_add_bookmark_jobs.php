<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBookmarkJobs extends Migration
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

        Schema::create('bookmark_jobs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('jobid');
            $table->integer('authid');
            $table->string('status');
            $table->timestamps();
        });
        $this->create_enum('bookmark_status',"'0', '1'");
         DB::statement('ALTER TABLE bookmark_jobs ALTER COLUMN status TYPE bookmark_status  USING (status::bookmark_status)');
         DB::statement("ALTER TABLE bookmark_jobs ALTER COLUMN status SET DEFAULT '1'");
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
