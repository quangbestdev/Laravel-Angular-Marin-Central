<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterViewReview extends Migration
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
        DB::statement("CREATE OR REPLACE VIEW reviewsview AS select ROUND(AVG(rating),2) AS totalrating, COUNT(*) as totalreviewed,toid from service_request_reviews where isdeleted='0' and parent_id=0 group by toid");
        Schema::table('auths', function (Blueprint $table) {
            $table->string('adminsubtype')->nullable();
        });
        $this->create_enum('adminsubtype',"'admin','superadmin'");
        DB::statement('ALTER TABLE auths ALTER COLUMN adminsubtype TYPE adminsubtype USING (adminsubtype::adminsubtype) ');
        DB::statement("UPDATE auths SET adminsubtype='superadmin' where usertype='admin'");
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
