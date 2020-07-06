<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldsWebhook extends Migration
{   
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()     
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->string('subscription_id')->nullable();
            $table->string('kind')->nullable();
            $table->string('transaction')->nullable();
            $table->decimal('amount',10,2)->nullable();
        });
        DB::statement("ALTER TABLE webhooks ALTER COLUMN subscription_id SET DEFAULT NULL");
        DB::statement("ALTER TABLE webhooks ALTER COLUMN kind SET DEFAULT NULL");
        DB::statement("ALTER TABLE webhooks ALTER COLUMN transaction SET DEFAULT NULL");
        DB::statement("ALTER TABLE webhooks ALTER COLUMN amount SET DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::dropIfExists('boat-engine-companies');
    }
}
