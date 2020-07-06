<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanydetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companydetails', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('authid');
            $table->text('name');
            $table->text('slug');
            $table->json('services');
            $table->text('address')->nullable();
            $table->string('city',255);
            $table->string('state',255);
            $table->string('country',255);
            $table->string('zipcode',255);
            $table->string('contact',255);
            $table->text('about');
            $table->string('businessemail',255);
            $table->string('websiteurl',255)->nullable();
            $table->json('images')->nullable();
            $table->decimal('longitude',20,10);
            $table->decimal('latitude',20,10);
            $table->timestamp('nextpaymentdate')->nullable();
            $table->text('stripe_acc_id')->nullable();
            $table->integer('paymentplan')->default('0');
            $table->string('plansubtype')->nullable();;
            $table->string('subscriptiontype')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('companydetails');
    }
}
