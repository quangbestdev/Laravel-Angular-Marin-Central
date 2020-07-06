<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEnum extends Migration
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
        $this->create_enum('usertype',"'admin', 'regular','company','professional','yacht'");
        $this->create_enum('stepscompleted',"'1', '2','3'");
        $this->create_enum('status',"'active', 'deleted','pending','suspended'");
        $this->create_enum('plan_status',"'active', 'inactive','deleted'");
        $this->create_enum('transactionfor',"'registrationfee', 'leadfee','resumefee','geolocationfee'");
        $this->create_enum('paymentstatus',"'pending', 'declined','approved'");
        $this->create_enum('jobstatus',"'posted', 'received_leads','deleted'");
        $this->create_enum('rating',"'1','2','3','4','5'");
        $this->create_enum('service_status',"'0', '1'");
        $this->create_enum('review_status',"'0', '1'");
        $this->create_enum('rating_status',"'0', '1'");
        $this->create_enum('isread_status',"'0', '1'");
        $this->create_enum('usareas_status',"'0', '1'");
        $this->create_enum('subscription_type',"'automatic', 'manual'");
        $this->create_enum('plantype',"'free', 'paid'");  
        $this->create_enum('planaccesstype',"'day', 'month', 'year', 'unlimited'"); 
        $this->create_enum('category_status',"'0', '1'"); 
        $this->create_enum('location_status',"'0', '1'");
        $this->create_enum('plansubtype',"'free', 'paid'");
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
