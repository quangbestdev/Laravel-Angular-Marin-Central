<?php

use Illuminate\Database\Seeder;

class SubscriptionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
		$currentTime = date("Y-m-d H:i:s");
		$plansArr = [
			['planname' => 'Free','plandescription' => 'Set up business profile page and enhance your SEO by creating additional
webpages for your business! Includes up to (3) geo-targeted areas to promote
your business.','amount' => '0','resumeaccess' => '3','leadaccess' => '0','geolocationaccess' => '1','plantype' => 'free','planaccessnumber' => '0','planaccesstype' => 'unlimited','status' => 'active','isadminplan' => '0','created_at' => $currentTime, 'updated_at' => $currentTime],
			['planname' => 'Basic','plandescription' => 'Basic Plan','amount' => '199','resumeaccess' => '5','leadaccess' => '5','geolocationaccess' => '3','plantype' => 'paid','planaccessnumber' => '1','planaccesstype' => 'month','status' => 'active','isadminplan' => '0','created_at' => $currentTime, 'updated_at' => $currentTime],
			['planname' => 'Advanced','plandescription' => 'Advanced Plan','amount' => '299','resumeaccess' => '10','leadaccess' => '10','geolocationaccess' => '10','plantype' => 'paid','planaccessnumber' => '1','planaccesstype' => 'month','status' => 'active','isadminplan' => '0','created_at' => $currentTime, 'updated_at' => $currentTime],
			['planname' => 'Marine Pro','plandescription' => 'Marine Pro Plan','amount' => '399','resumeaccess' => '15','leadaccess' => '9999','geolocationaccess' => '9999','plantype' => 'paid','planaccessnumber' => '1','planaccesstype' => 'month','status' => 'active','isadminplan' => '0','created_at' => $currentTime, 'updated_at' => $currentTime],
			['planname' => 'Free','plandescription' => 'Unlimited Plan','amount' => '0','resumeaccess' => '50','leadaccess' => '50','geolocationaccess' => '50','plantype' => 'free','planaccessnumber' => '0','planaccesstype' => 'unlimited','status' => 'active','isadminplan' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
			];
		DB::table('subscriptionplans')->delete();
		foreach($plansArr as $plan){
			DB::table('subscriptionplans')->insert($plan);
		}
	}
}
