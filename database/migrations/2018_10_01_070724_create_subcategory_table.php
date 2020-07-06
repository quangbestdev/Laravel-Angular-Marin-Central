<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubcategoryTable extends Migration
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
        Schema::create('subcategory', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('category_id');
            $table->string('subcategory_name');
            $table->string('status');            
            $table->timestamps();
        });
        $this->create_enum('subcategory_status',"'0', '1'");
        DB::statement('ALTER TABLE subcategory ALTER COLUMN status TYPE subcategory_status  USING (status::subcategory_status)');
        DB::statement('ALTER TABLE subcategory ALTER COLUMN status TYPE subcategory_status  USING (status::subcategory_status)');
        DB::statement('ALTER TABLE services ADD COLUMN subcategory integer DEFAULT 0');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subcategory');
    }
}
