<?php

use Illuminate\Database\Seeder;

class NewPlansSeeding extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('ALTER TABLE subscriptionplans ALTER COLUMN active_status TYPE plan_status USING (active_status::plan_status)');
        DB::statement("UPDATE subscriptionplans SET active_status = 'inactive' where planname != 'Free' AND isadminplan != '1'");
        DB::statement("UPDATE subscriptionplans SET active_status = 'active' where planname = 'Free' AND isadminplan = '0'");
        DB::table('subscriptionplans')->insert(['planname' => 'Basic','plandescription' => 'New Basic Plan','amount' => '47','resumeaccess' => '5','leadaccess' => '99999','geolocationaccess' => '3','plantype' => 'paid','planaccessnumber' => '1','planaccesstype' => 'month','status' => 'active','isadminplan' => '0','created_at' => '2019-02-27 10:19:10', 'updated_at' => '2019-02-27 10:19:10','active_status' => 'active','stripe_plan_id' => 'new_basic_monthly_plan']);
        DB::table('subscriptionplans')->insert(['planname' => 'Pro','plandescription' => 'Pro Plan','amount' => '297','resumeaccess' => '15','leadaccess' => '99999','geolocationaccess' => '99999','plantype' => 'paid','planaccessnumber' => '1','planaccesstype' => 'year','status' => 'active','isadminplan' => '0','created_at' => '2019-02-27 10:19:10', 'updated_at' => '2019-02-27 10:19:10','active_status' => 'active','stripe_plan_id' => 'new_pro_plan']);
    }
}
