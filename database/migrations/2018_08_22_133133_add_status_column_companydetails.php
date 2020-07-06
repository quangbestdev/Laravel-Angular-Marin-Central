<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusColumnCompanydetails extends Migration
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
        Schema::table('companydetails', function (Blueprint $table) {
            $table->string('is_admin_approve')->nullable();
            $table->string('is_claimed')->nullable();
        });
        Schema::table('auths', function (Blueprint $table) {
            $table->string('is_activated')->nullable();
            $table->text('activation_hash')->nullable();
        });
        $this->create_enum('is_admin_approve',"'0', '1'");
        $this->create_enum('is_activated',"'0', '1'");
        
        DB::statement('ALTER TABLE companydetails ALTER COLUMN is_admin_approve TYPE is_admin_approve  USING (is_admin_approve::is_admin_approve)');
        $this->create_enum('is_claimed',"'0', '1'");
        DB::statement("ALTER TABLE companydetails ALTER COLUMN is_admin_approve SET DEFAULT '1'");
        DB::statement('ALTER TABLE companydetails ALTER COLUMN is_claimed TYPE is_claimed  USING (is_claimed::is_claimed)');
        DB::statement("ALTER TABLE companydetails ALTER COLUMN is_claimed SET DEFAULT '0'");
        $this->create_enum('is_activated',"'0', '1'");
        DB::statement('ALTER TABLE auths ALTER COLUMN is_activated TYPE is_activated  USING (is_activated::is_activated)');
        DB::statement("ALTER TABLE auths ALTER COLUMN is_activated SET DEFAULT '0'");
        DB::statement("UPDATE auths set is_activated='1'");
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
