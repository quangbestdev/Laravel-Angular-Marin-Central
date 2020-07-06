<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusColumn extends Migration
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
        Schema::table('companydetails', function($table) {
            $table->string('status')->nullable();
        });
        Schema::table('talentdetails', function($table) {
            $table->string('status')->nullable();
        });
        Schema::table('userdetails', function($table) {
            $table->string('status')->nullable();
        });
        Schema::table('yachtdetail', function($table) {
            $table->string('status')->nullable();
        });
        $this->create_enum('advertisebusiness',"'0', '1'");
        DB::statement("ALTER TABLE companydetails ADD COLUMN advertisebusiness advertisebusiness NOT NULL DEFAULT '0'");
        DB::statement("ALTER TABLE companydetails ADD COLUMN primaryimage text  DEFAULT NULL");
        DB::statement("ALTER TABLE companydetails ADD COLUMN allservices json  DEFAULT NULL");
        DB::statement('ALTER TABLE companydetails ALTER COLUMN status TYPE status USING (status::status)');
        DB::statement("ALTER TABLE companydetails ALTER COLUMN status SET DEFAULT 'pending'");
        
        DB::statement('ALTER TABLE talentdetails ALTER COLUMN status TYPE status USING (status::status)');
        DB::statement("ALTER TABLE talentdetails ALTER COLUMN status SET DEFAULT 'pending'");
        
        DB::statement('ALTER TABLE userdetails ALTER COLUMN status TYPE status USING (status::status)');
        DB::statement("ALTER TABLE userdetails ALTER COLUMN status SET DEFAULT 'pending'");
        
        DB::statement('ALTER TABLE yachtdetail ALTER COLUMN status TYPE status USING (status::status)');
        DB::statement("ALTER TABLE yachtdetail ALTER COLUMN status SET DEFAULT 'pending'");
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companydetails', function($table) {
            $table->dropColumn('status');
        });
        Schema::table('talentdetails', function($table) {
            $table->dropColumn('status');
        });
        Schema::table('userdetails', function($table) {
            $table->dropColumn('status');
        });
        Schema::table('yachtdetail', function($table) {
            $table->dropColumn('status');
        });
    }
}
