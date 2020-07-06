<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {	
        $this->call(AuthsTableSeeder::class);
        $this->call(BadwordSeeder::class);
        $this->call(SubscriptionTableSeeder::class);
        $this->call(UsareasSeeder::class);
        $this->call(EmailtemplateSeeder::class);
        $this->call(CountryCodeTableSeeder::class);
        $this->call(DiscountSeeder::class);
        $this->call(NewPlansSeeding::class);
    }
}
