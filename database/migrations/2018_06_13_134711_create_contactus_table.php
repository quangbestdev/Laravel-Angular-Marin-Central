<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactusTable extends Migration
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
        Schema::create('contactus', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('authid')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('contact_no',100)->nullable();
            $table->string('subject');
            $table->text('message');
            $table->string('status');
            $table->string('is_read');
            $table->timestamps();
        });
        $this->create_enum('contactus_status',"'0', '1'");
        $this->create_enum('is_read',"'0', '1'");
        DB::statement('ALTER TABLE contactus ALTER COLUMN status TYPE contactus_status  USING (status::contactus_status)');
        DB::statement('ALTER TABLE contactus ALTER COLUMN is_read TYPE is_read  USING (is_read::is_read)');  
        DB::statement("ALTER TABLE contactus ALTER COLUMN is_read SET DEFAULT '0'");  
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contactus');
    }
}
