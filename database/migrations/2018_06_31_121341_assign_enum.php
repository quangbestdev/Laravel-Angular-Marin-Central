<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AssignEnum extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE auths ALTER COLUMN usertype TYPE usertype USING (usertype::usertype) ');
        DB::statement('ALTER TABLE auths ALTER COLUMN stepscompleted TYPE stepscompleted USING (stepscompleted::stepscompleted) ');
        DB::statement('ALTER TABLE auths ALTER COLUMN status TYPE status USING (status::status)');
        DB::statement('ALTER TABLE services ALTER COLUMN status TYPE service_status USING (status::service_status)');
        DB::statement('ALTER TABLE subscriptionplans ALTER COLUMN status TYPE plan_status USING (status::plan_status)');
        DB::statement('ALTER TABLE paymenthistory ALTER COLUMN transactionfor TYPE transactionfor USING (transactionfor::transactionfor)');
        DB::statement('ALTER TABLE paymenthistory ALTER COLUMN status TYPE paymentstatus USING (status::paymentstatus)');
        DB::statement('ALTER TABLE users_jobs ALTER COLUMN status TYPE jobstatus USING (status::jobstatus)');
        DB::statement('ALTER TABLE users_jobs ALTER COLUMN paymentstatus TYPE paymentstatus USING (paymentstatus::paymentstatus)');
        DB::statement('ALTER TABLE approachedtalents ALTER COLUMN paymentstatus TYPE paymentstatus USING (paymentstatus::paymentstatus)');
        DB::statement('ALTER TABLE broadcast ALTER COLUMN isread TYPE isread_status USING (isread::isread_status)');
        DB::statement('ALTER TABLE companydetails ALTER COLUMN subscriptiontype TYPE subscription_type  USING (subscriptiontype::subscription_type)');
        DB::statement('ALTER TABLE usareas ALTER COLUMN status TYPE usareas_status  USING (status::usareas_status)');
        DB::statement('ALTER TABLE subscriptionplans ALTER COLUMN plantype TYPE plantype  USING (plantype::plantype)');
        DB::statement('ALTER TABLE subscriptionplans ALTER COLUMN planaccesstype TYPE planaccesstype  USING (planaccesstype::planaccesstype)');
        DB::statement('ALTER TABLE category ALTER COLUMN status TYPE category_status  USING (status::category_status)');
        DB::statement('ALTER TABLE geolocation ALTER COLUMN status TYPE location_status  USING (status::location_status)');
        // DB::statement('ALTER TABLE companydetails ADD COLUMN plansubtype plansubtype');
    }
   
  
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         
    }
}
