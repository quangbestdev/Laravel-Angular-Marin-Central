<?php

use Illuminate\Database\Seeder;

class AuthsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('auths')->insert([
            'email' => 'superadmin@gmail.com',
            'password' => Hash::make('test'),
            'usertype' => 'admin',
            'status'=>'active',
            'firstname_admin' => 'Super',
            'lastname_admin' => 'Admin',
            'adminsubtype' => 'superadmin',
            'ipaddress'=>'1.2.3.4',
            'stepscompleted'=>'3'
        ]);
    }
}
