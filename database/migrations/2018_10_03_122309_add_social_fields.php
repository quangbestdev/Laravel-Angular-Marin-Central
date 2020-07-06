<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSocialFields extends Migration
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
        Schema::table('auths', function (Blueprint $table) {
            $table->string('is_social')->nullable();
            $table->string('provider')->nullable();
            $table->string('social_id',255)->nullable(); 
        });
        Schema::table('dummy_registration', function (Blueprint $table) {
            $table->string('is_social')->nullable();
            $table->string('provider')->nullable();
            $table->string('social_id',255)->nullable();
        });
        Schema::table('rejected_registration', function (Blueprint $table) {
            $table->string('is_social')->nullable();
            $table->string('provider')->nullable();
            $table->string('social_id',255)->nullable();
        });
         $this->create_enum('is_social',"'0', '1'");
        DB::statement('ALTER TABLE auths ALTER COLUMN is_social TYPE is_social  USING (is_social::is_social)');
        DB::statement('ALTER TABLE dummy_registration ALTER COLUMN is_social TYPE is_social  USING (is_social::is_social)');
        DB::statement('ALTER TABLE rejected_registration ALTER COLUMN is_social TYPE is_social  USING (is_social::is_social)'); 
        DB::statement("ALTER TABLE auths ALTER COLUMN password DROP NOT NULL");
        DB::statement("ALTER TABLE dummy_registration ALTER COLUMN password DROP NOT NULL");
        DB::statement("ALTER TABLE rejected_registration ALTER COLUMN password DROP NOT NULL");

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
