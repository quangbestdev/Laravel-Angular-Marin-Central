<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTablesNameAndFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {   
        Schema::table('users_jobs', function($table) {
            $table->dropColumn('budget');
            $table->dropColumn('image');
            $table->dropColumn('paymentstatus');
            $table->text('addspecialrequirement')->nullable();
        });
        Schema::rename('users_jobs', 'users_service_requests');
        Schema::table('users_service_requests', function(Blueprint $table)
        {
            $table->string('title',255);
        });
        DB::statement("ALTER TABLE paymenthistory RENAME COLUMN jobid TO requestid;");
        Schema::table('talentdetails', function(Blueprint $table)
        {
            $table->string('otherjobtitle',255)->nullable();
            $table->integer('totalexperience')->nullable();
        });
        DB::statement("ALTER TABLE talentdetails RENAME COLUMN jobtitle TO jobtitleid;");
        DB::statement("TRUNCATE talentdetails");
        DB::statement("ALTER TABLE talentdetails ALTER COLUMN jobtitleid TYPE integer USING (jobtitleid::integer)");
        DB::statement("ALTER TABLE jobs_proposals RENAME COLUMN jobid TO requestid;");
        DB::statement("ALTER TABLE proposal_transactions RENAME COLUMN jobid TO requestid;");
        Schema::rename('jobs_proposals', 'request_proposals');
        Schema::rename('jobrating', 'service_request_ratings');
        Schema::rename('jobreviews', 'service_request_reviews');
        DB::statement("CREATE OR REPLACE VIEW reviewsview AS select ROUND(AVG(rating),2) AS totalrating, COUNT(*) as totalreviewed,toid from service_request_reviews where isdeleted='0' group by toid");
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
