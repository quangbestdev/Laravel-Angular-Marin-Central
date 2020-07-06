<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUpdateCompanydetailsTable extends Migration
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
        $this->create_enum('plansubtype',"'free', 'paid'");
        DB::statement('ALTER TABLE companydetails ALTER COLUMN plansubtype TYPE plansubtype  USING (plansubtype::plansubtype)');
        DB::statement('ALTER TABLE jobreviews ALTER COLUMN isdeleted TYPE review_status USING (isdeleted::review_status)');
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::dropIfExists('update_companydetails');
    }
}
