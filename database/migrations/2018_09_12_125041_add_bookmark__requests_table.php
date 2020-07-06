<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBookmarkRequestsTable extends Migration
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
       Schema::create('bookmark_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('requestid');
            $table->integer('authid');
            $table->string('status');
            $table->timestamps();
        });
        $this->create_enum('bookmarkrequest_status',"'0', '1'");
         DB::statement('ALTER TABLE bookmark_requests ALTER COLUMN status TYPE bookmarkrequest_status  USING (status::bookmarkrequest_status)');
         DB::statement("ALTER TABLE bookmark_requests ALTER COLUMN status SET DEFAULT '1'");
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
