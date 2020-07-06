<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableDummyRegistration extends Migration
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
        Schema::create('dummy_registration', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('authid')->nullable();
            $table->string('email');
            $table->string('password');
            $table->string('usertype');
            $table->string('ipaddress',100);
            $table->string('stepscompleted');

            $table->string('firstname',255)->nullable();
            $table->string('lastname',255)->nullable();
            $table->string('sex')->nullable();
            $table->string('city',255)->nullable();
            $table->string('state',255)->nullable();
            $table->string('country',255)->nullable();
            $table->string('zipcode',255)->nullable();
            $table->string('county',255)->nullable();
            $table->text('address')->nullable();
            $table->string('mobile',255)->nullable();
            $table->string('birthdate',255)->nullable();
            $table->string('profile_image')->nullable();
            $table->decimal('longitude',20,10)->nullable();
            $table->decimal('latitude',20,10)->nullable();

            $table->string('contact',255)->nullable();    
            $table->json('yachtdetail')->nullable();
            $table->string('homeport',255)->nullable();           
            $table->json('images')->nullable();
            $table->text('primaryimage')->nullable();
            $table->string('coverphoto')->nullable();

            $table->text('name')->nullable();
            $table->text('slug')->nullable();
            $table->json('services')->nullable();
            $table->text('about')->nullable();
            $table->string('businessemail',255)->nullable();
            $table->string('websiteurl',255)->nullable();
            $table->timestamp('nextpaymentdate')->nullable();
            $table->text('stripe_acc_id')->nullable();
            $table->integer('paymentplan')->default('0')->nullable();
            $table->string('plansubtype')->nullable();
            $table->string('subscriptiontype')->nullable();
            $table->json('allservices')->nullable();
            $table->string('contactname',255)->nullable();
            $table->string('contactmobile',255)->nullable();
            $table->string('contactemail',255)->nullable();
            $table->string('actualslug',255)->nullable();
            
            $table->string('jobtitle',255)->nullable();
            $table->integer('jobtitleid')->nullable();
            $table->json('licences')->nullable();
            $table->json('certification')->nullable();
            $table->text('objective')->nullable();
            $table->json('workexperience')->nullable();
            $table->string('willingtravel')->nullable();
            $table->string('otherjobtitle')->nullable();    
            $table->integer('totalexperience')->nullable();
            $table->text('resume')->nullable();
            $table->string('is_claim_user');
        });
        $this->create_enum('is_claim_user',"'0', '1'");
        DB::statement('ALTER TABLE dummy_registration ALTER COLUMN is_claim_user TYPE is_claim_user  USING (is_claim_user::is_claim_user)');
        DB::statement('ALTER TABLE dummy_registration ALTER COLUMN plansubtype TYPE plansubtype  USING (plansubtype::plansubtype)');
        DB::statement('ALTER TABLE dummy_registration ALTER COLUMN subscriptiontype TYPE subscription_type  USING (subscriptiontype::subscription_type)');
        DB::statement('ALTER TABLE dummy_registration ALTER COLUMN usertype TYPE usertype USING (usertype::usertype) ');
        DB::statement('ALTER TABLE dummy_registration ALTER COLUMN stepscompleted TYPE stepscompleted USING (stepscompleted::stepscompleted) ');
        DB::statement('ALTER TABLE messages ALTER COLUMN parent_id SET DEFAULT 0');
        DB::statement("ALTER TABLE dummy_registration ALTER COLUMN is_claim_user SET DEFAULT '0'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::dropIfExists('dummy_registration');
    }
}
