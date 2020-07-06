<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuoterequestsTable extends Migration
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
        Schema::create('quoterequests', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('businessid');
            $table->integer('userid')->nullable();
            $table->string('title',255)->nullable();
            $table->text('objective');
            $table->string('name',255);
            $table->string('email',255);
            $table->string('is_read');
            $table->string('status');
            $table->timestamps();
        });
        $this->create_enum('quotes_isread',"'0', '1'");
        $this->create_enum('quote_status',"'0', '1'");
        DB::statement("ALTER TABLE quoterequests ALTER COLUMN status TYPE quote_status  USING (status::quote_status)");
        DB::statement("ALTER TABLE quoterequests ALTER COLUMN is_read TYPE quotes_isread USING (is_read::quotes_isread)");
         DB::statement("ALTER TABLE quoterequests ALTER COLUMN status  SET DEFAULT '0'");
        DB::statement("ALTER TABLE quoterequests ALTER COLUMN is_read  SET DEFAULT '0'");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quoterequests');
    }
}
