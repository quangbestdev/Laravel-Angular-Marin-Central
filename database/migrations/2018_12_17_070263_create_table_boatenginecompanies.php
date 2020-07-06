<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableBoatenginecompanies extends Migration
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
        Schema::create('boat_engine_companies', function (Blueprint $table) {
            $table->increments('id');
            $table->text('name');
            $table->string('category');
            $table->string('status');
            $table->timestamps();
        });
        $this->create_enum('boat_engine_companies_category',"'boats', 'yachts','engines'");
        $this->create_enum('boat_engine_companies_status',"'0', '1'");
        DB::statement('ALTER TABLE boat_engine_companies ALTER COLUMN category TYPE boat_engine_companies_category  USING (category::boat_engine_companies_category)');
        DB::statement("ALTER TABLE boat_engine_companies ALTER COLUMN category SET DEFAULT 'boats'");
        DB::statement('ALTER TABLE boat_engine_companies ALTER COLUMN status TYPE boat_engine_companies_status  USING (status::boat_engine_companies_status)');
        DB::statement("ALTER TABLE boat_engine_companies ALTER COLUMN status SET DEFAULT '1'");
        Schema::table('companydetails', function (Blueprint $table) {
            $table->json('boats_yachts_worked')->nullable();
            $table->json('engines_worked')->nullable();
        });
        DB::statement("ALTER TABLE companydetails ALTER COLUMN boats_yachts_worked SET DEFAULT NULL");
        DB::statement("ALTER TABLE companydetails ALTER COLUMN engines_worked SET DEFAULT NULL");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::dropIfExists('boat-engine-companies');
    }
}
