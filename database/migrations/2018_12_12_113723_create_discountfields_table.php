<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDiscountfieldsTable extends Migration
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
		$this->create_enum('isuseddiscount',"'0', '1'");
        Schema::table('companydetails', function (Blueprint $table) {
            $table->integer('remaindiscount')->nullable();
            $table->integer('remaintrial')->nullable();
            $table->timestamp('lastpaymentdate')->nullable();
            $table->integer('next_paymentplan')->default('0');
        });
        DB::statement("ALTER TABLE companydetails ALTER COLUMN remaindiscount SET DEFAULT 12");
        DB::statement("ALTER TABLE companydetails ALTER COLUMN remaintrial SET DEFAULT 30");
        DB::statement("UPDATE companydetails SET remaindiscount=12");
        DB::statement("UPDATE companydetails SET remaintrial=30");
        
        Schema::table('dummy_registration', function (Blueprint $table) {
            $table->integer('remaindiscount')->nullable();
            $table->integer('remaintrial')->nullable();
            $table->timestamp('lastpaymentdate')->nullable();
        });
        DB::statement("ALTER TABLE dummy_registration ALTER COLUMN remaindiscount SET DEFAULT 12");
        DB::statement("ALTER TABLE dummy_registration ALTER COLUMN remaintrial SET DEFAULT 30");
        DB::statement("UPDATE dummy_registration SET remaindiscount=12");
        DB::statement("UPDATE dummy_registration SET remaintrial=30");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Schema::dropIfExists('discounts');
    }
}
