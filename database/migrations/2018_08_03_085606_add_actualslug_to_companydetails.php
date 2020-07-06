<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddActualslugToCompanydetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::table('companydetails', function($table)
        {
            $table->text('actualslug')->nullable();
        });
        DB::table('subscriptionplans')->insert(['planname' => 'Free Plan','plandescription' => 'Unlimited Plan','amount' => '0','resumeaccess' => '50','leadaccess' => '50','geolocationaccess' => '50','plantype' => 'free','planaccessnumber' => '0','planaccesstype' => 'unlimited','status' => 'active','isadminplan' => '1','created_at' => '2018-07-20 10:19:10', 'updated_at' => '2018-07-20 10:19:10']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */

    public function down()
    {
        Schema::table('companydetails', function ($table) {
            $table->dropColumn('actualslug');
        });
    }
}
