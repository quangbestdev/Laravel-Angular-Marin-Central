<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRequestsAlter extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE users_service_requests ALTER COLUMN description  DROP NOT NULL');
        DB::statement('ALTER TABLE users_service_requests ALTER COLUMN numberofleads  DROP NOT NULL');
        DB::statement('ALTER TABLE users_service_requests ALTER COLUMN city  DROP NOT NULL');
        DB::statement('ALTER TABLE users_service_requests ALTER COLUMN state  DROP NOT NULL');
        DB::statement('ALTER TABLE users_service_requests ALTER COLUMN country  DROP NOT NULL');
        DB::statement('ALTER TABLE users_service_requests ALTER COLUMN zipcode  DROP NOT NULL');
        DB::statement('ALTER TABLE users_service_requests ALTER COLUMN longitude  DROP NOT NULL');
        DB::statement('ALTER TABLE users_service_requests ALTER COLUMN latitude  DROP NOT NULL');
        DB::statement('ALTER TABLE users_service_requests ALTER COLUMN status  DROP NOT NULL');
        DB::statement('ALTER TABLE users_service_requests ALTER COLUMN title  DROP NOT NULL');
        DB::statement('ALTER TABLE users_service_requests ALTER COLUMN optionalinfo  DROP NOT NULL');
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
