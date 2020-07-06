<?php

use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$currentTime = date("Y-m-d H:i:s");
        DB::table('discounts')->delete();
		$allrecords =  DB::table('subscriptionplans')->get();
		if(!empty($allrecords)) {
			foreach ($allrecords as $key => $value) {
				if($value->plantype == 'paid' &&  $value->amount > 0 ) {
					DB::table('discounts')->insert(['paymentplan' => $value->id ,'current_discount' => 50,'created_at' => $currentTime,'updated_at' => $currentTime]);
				}
			}
		}
    }
}
