<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('auths')->insert([
            'email' => 'admin@msp.com',
            'password' => Hash::make('marine@#123'),
            'usertype' => 'admin',
            'status'=>'active',
            'ipaddress'=>'1.2.3.4',
            'stepscompleted'=>'3'
        ]);
    }
}
