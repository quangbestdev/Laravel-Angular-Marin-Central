<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAccountTypeCompanydetails extends Migration
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
            $table->string('account_type')->nullable();
            $table->string('subscription_reminder')->nullable();
            $table->string('free_subscription_period')->nullable();
        });
        $this->create_enum('account_type',"'paid', 'free'");
        DB::statement("ALTER TABLE companydetails ALTER COLUMN account_type TYPE account_type  USING (account_type::account_type)");
        $this->create_enum('subscription_reminder',"'0', '1'");
        DB::statement("ALTER TABLE companydetails ALTER COLUMN subscription_reminder TYPE subscription_reminder  USING (subscription_reminder::subscription_reminder)");
        DB::statement("UPDATE companydetails SET account_type='paid'");
        // DB::statement("ALTER TABLE companydetails ALTER COLUMN account_type SET NOT NULL");
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
