<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDiscountTable extends Migration
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
        Schema::create('discounts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('paymentplan');
            $table->integer('current_discount');
            $table->timestamps();
        });
        $this->create_enum('is_discount',"'0', '1'");
        
        Schema::table('dummy_registration', function (Blueprint $table) {
            $table->string('is_discount')->nullable();
            $table->integer('discount')->nullable();
        });
        
        
        DB::statement("ALTER TABLE dummy_registration ALTER COLUMN is_discount TYPE is_discount  USING (is_discount::is_discount)");
        DB::statement("ALTER TABLE dummy_registration ALTER COLUMN is_discount SET DEFAULT '0'");
        DB::statement("UPDATE dummy_registration SET is_discount='0'");
        DB::statement("ALTER TABLE dummy_registration ALTER COLUMN discount SET DEFAULT 0");
        DB::statement("UPDATE dummy_registration SET discount=0");
        
        Schema::table('companydetails', function (Blueprint $table) {
            $table->string('is_discount')->nullable();
            $table->integer('discount')->nullable();
        });
        
        
        DB::statement("ALTER TABLE companydetails ALTER COLUMN is_discount TYPE is_discount  USING (is_discount::is_discount)");
        DB::statement("ALTER TABLE companydetails ALTER COLUMN is_discount SET DEFAULT '0'");
        DB::statement("UPDATE companydetails SET is_discount='0'");
        DB::statement("ALTER TABLE companydetails ALTER COLUMN discount SET DEFAULT 0");
        DB::statement("UPDATE companydetails SET discount=0");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('discounts');
    }
}
