<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBlogTable extends Migration
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
        Schema::create('blogs', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title',255);
                $table->text('description');
                $table->string('videourl',255)->nullable();
                $table->text('blogimage');
                $table->string('status');
                $table->timestamps();
            });
        $this->create_enum('blog_status',"'created', 'publish','deleted'");
         DB::statement('ALTER TABLE blogs ALTER COLUMN status TYPE blog_status  USING (status::blog_status)');
         DB::statement("ALTER TABLE blogs ALTER COLUMN status SET DEFAULT 'created'");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('blogs');
    }
}
