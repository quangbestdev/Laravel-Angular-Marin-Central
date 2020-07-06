<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobtitlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
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
    public function up()
    {
        Schema::create('jobtitles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title',255);
            $table->string('status');
            $table->timestamps();
        });
        $this->create_enum('jobtitle_status',"'0', '1'");
        DB::statement('ALTER TABLE jobtitles ALTER COLUMN status TYPE jobtitle_status  USING (status::jobtitle_status)');
        DB::table('jobtitles')->insert(['title' => 'Others','status' => '1','created_at' => '2018-07-20 10:19:10','updated_at' => '2018-07-20 10:19:10']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jobtitles');
    }
}
