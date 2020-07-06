<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClaimedBusinessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('claimed_business', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->string('password');
            $table->string('stepscompleted');
            $table->integer('authid');
            $table->text('name')->nullable();
            $table->json('services')->nullable();
            $table->text('address')->nullable();
            $table->string('city',255)->nullable();
            $table->string('state',255)->nullable();
            $table->string('country',255)->nullable();
            $table->string('zipcode',255)->nullable();
            $table->string('contact',255)->nullable();
            $table->text('about');
            $table->string('businessemail',255);
            $table->string('websiteurl',255)->nullable();
            $table->string('contactname',255)->nullable();
            $table->string('contactmobile',255)->nullable();
            $table->string('contactemail',255)->nullable();
            $table->json('images')->nullable();
            $table->string('coverphoto',255)->nullable();
            $table->decimal('longitude',20,10)->nullable();
            $table->decimal('latitude',20,10)->nullable();
            $table->timestamp('nextpaymentdate')->nullable();
            $table->text('stripe_acc_id')->nullable();
            $table->integer('paymentplan')->default('0');
            $table->string('plansubtype')->nullable();;
            $table->string('subscriptiontype')->nullable();
            $table->string('status')->nullable();
            $table->string('ipaddress',100);
            $table->timestamps();
        });
        DB::statement('ALTER TABLE claimed_business ALTER COLUMN stepscompleted TYPE stepscompleted USING (stepscompleted::stepscompleted) ');

        DB::statement('ALTER TABLE claimed_business ALTER COLUMN plansubtype TYPE plansubtype  USING (plansubtype::plansubtype)');
        DB::statement('ALTER TABLE claimed_business ALTER COLUMN subscriptiontype TYPE subscription_type  USING (subscriptiontype::subscription_type)');
        DB::statement("ALTER TABLE claimed_business ADD COLUMN advertisebusiness advertisebusiness NOT NULL DEFAULT '0'");
        DB::statement("ALTER TABLE claimed_business ADD COLUMN primaryimage text  DEFAULT NULL");
        DB::statement("ALTER TABLE claimed_business ADD COLUMN allservices json  DEFAULT NULL");
        DB::statement('ALTER TABLE claimed_business ALTER COLUMN status TYPE status USING (status::status)');
        DB::statement("ALTER TABLE claimed_business ALTER COLUMN status SET DEFAULT 'pending'");
        DB::statement("ALTER TABLE claimed_business ADD COLUMN accounttype accounttype NOT NULL DEFAULT 'real'");
        DB::statement("ALTER TABLE claimed_business ALTER COLUMN businessemail  DROP NOT NULL ");
        DB::statement("ALTER TABLE claimed_business ALTER COLUMN about  DROP NOT NULL "); 
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('claimed_business');
    }
}
