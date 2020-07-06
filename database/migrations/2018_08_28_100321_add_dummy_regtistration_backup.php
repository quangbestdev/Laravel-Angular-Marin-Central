<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDummyRegtistrationBackup extends Migration
{
    public function create_enum($name, $strings) {
        DB::statement("DO $$
        BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = '" . $name ."') THEN
                CREATE TYPE " .  $name . " AS ENUM
                (
                    " . $strings . "
                );
            END IF;
        END$$;");
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()     
    {
        Schema::create('dummy_registration_backup', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('authid')->nullable();
            $table->string('email');
            $table->string('password');
            $table->string('ipaddress',100);
            $table->text('name')->nullable();
            $table->text('slug')->nullable();
            $table->json('services')->nullable();
            $table->text('address')->nullable();
            $table->string('city',255)->nullable();
            $table->string('state',255)->nullable();
            $table->string('country',255)->nullable();
            $table->string('zipcode',255)->nullable();
            $table->string('county',255)->nullable();
            $table->string('contact',255)->nullable();  
            $table->text('about')->nullable();
            $table->string('businessemail',255)->nullable();
            $table->string('websiteurl',255)->nullable();
            $table->json('images')->nullable();
            $table->decimal('longitude',20,10)->nullable();
            $table->decimal('latitude',20,10)->nullable();
            $table->timestamp('nextpaymentdate')->nullable();
            $table->text('stripe_acc_id')->nullable();
            $table->integer('paymentplan')->default('0')->nullable();
            $table->string('plansubtype')->nullable();
            $table->string('subscriptiontype')->nullable();
            $table->json('allservices')->nullable();
            $table->text('primaryimage')->nullable();
            $table->string('coverphoto')->nullable();
            $table->string('contactname',255)->nullable();
            $table->string('contactmobile',255)->nullable();
            $table->string('contactemail',255)->nullable();
            $table->string('actualslug',255)->nullable();
            $table->timestamps();
        });
        DB::statement('ALTER TABLE dummy_registration_backup ALTER COLUMN plansubtype TYPE plansubtype  USING (plansubtype::plansubtype)');
        DB::statement('ALTER TABLE dummy_registration_backup ALTER COLUMN subscriptiontype TYPE subscription_type  USING (subscriptiontype::subscription_type)');
        DB::statement("ALTER TABLE dummy_registration_backup ADD COLUMN advertisebusiness advertisebusiness NOT NULL DEFAULT '0'");
        DB::statement("ALTER TABLE dummy_registration_backup ADD COLUMN accounttype accounttype NOT NULL DEFAULT 'real'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::dropIfExists('dummy_registration_backup');
    }
}
