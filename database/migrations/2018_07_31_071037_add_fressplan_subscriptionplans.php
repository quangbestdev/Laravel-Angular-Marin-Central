<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFressplanSubscriptionplans extends Migration
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
        // Schema::table('yachtdetail', function (Blueprint $table) {
        //      $table->string('coverphoto',255)->nullable();
        // });
        // Schema::table('subscriptionplans', function($table)
        // {
        //     $table->string('isadminplan')->nullable();
        // });
        $this->create_enum('isadminplan',"'0', '1'");
        DB::statement("ALTER TABLE subscriptionplans ADD COLUMN isadminplan isadminplan NOT NULL DEFAULT '0'");
        
        // DB::statement("ALTER TABLE subscriptionplans ALTER COLUMN isadminplan TYPE isadminplan  USING (isadminplan::isadminplan)");
        // DB::statement("ALTER TABLE subscriptionplans ALTER COLUMN isadminplan DEFAULT '0' ");
        // Schema::table('companydetails', function($table)
        // {
        //     $table->string('accounttype')->nullable();
        // });
        $this->create_enum('accounttype',"'real', 'dummy'");
        DB::statement("ALTER TABLE companydetails ADD COLUMN accounttype accounttype NOT NULL DEFAULT 'real'");

        // DB::statement("ALTER TABLE companydetails ALTER COLUMN accounttype TYPE accounttype  USING (accounttype::accounttype)");
        // DB::statement("ALTER TABLE companydetails ALTER COLUMN accounttype  DEFAULT 'real'");
        DB::statement("ALTER TABLE companydetails ALTER COLUMN businessemail  DROP NOT NULL ");
        // Schema::table('companydetails', function(Blueprint $table)
        // {
        //     $table->string('businessemail',255)->nullable(false)->change();
        //     $table->text('about')->nullable(false)->change();
        // });
       DB::statement("ALTER TABLE companydetails ALTER COLUMN about  DROP NOT NULL "); 
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       Schema::table('subscriptionplans', function (Blueprint $table) {
             $table->dropColumn('isadminplan');
        });
        Schema::table('companydetails', function (Blueprint $table) {
             $table->dropColumn('accounttype');
        });
    }
}
