<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameStripidToCustomerId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE dummy_registration RENAME COLUMN stripe_acc_id TO customer_id;");
        DB::statement("ALTER TABLE companydetails RENAME COLUMN stripe_acc_id TO customer_id;");
        DB::statement("ALTER TABLE rejected_registration RENAME COLUMN stripe_acc_id TO customer_id;");
        DB::statement("ALTER TABLE dummy_registration_backup RENAME COLUMN stripe_acc_id TO customer_id;");
        DB::statement("ALTER TABLE paymenthistory RENAME COLUMN fingerprintid TO customer_id;");
        DB::statement("ALTER TABLE dummy_paymenthistory RENAME COLUMN fingerprintid TO customer_id;");
        DB::statement("ALTER TABLE rejected_paymenthistory RENAME COLUMN fingerprintid TO customer_id;");
        DB::statement('ALTER TABLE paymenthistory ALTER COLUMN tokenused SET DEFAULT NULL');
        DB::statement('ALTER TABLE paymenthistory ALTER COLUMN cardid SET DEFAULT NULL');
        DB::statement('ALTER TABLE dummy_paymenthistory ALTER COLUMN tokenused SET DEFAULT NULL');
        DB::statement('ALTER TABLE dummy_paymenthistory ALTER COLUMN cardid SET DEFAULT NULL');
        DB::statement('ALTER TABLE rejected_paymenthistory ALTER COLUMN tokenused SET DEFAULT NULL');
        DB::statement('ALTER TABLE rejected_paymenthistory ALTER COLUMN cardid SET DEFAULT NULL');
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
