<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class WebsiteReviews extends Migration
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
        Schema::create('website_reviews', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('authid');
            $table->integer('rating');
            $table->text('comment')->nullable();
            $table->string('isdeleted');
            $table->timestamps();
        });
        $this->create_enum('isdeleted',"'0', '1'");
        DB::statement("ALTER TABLE website_reviews ALTER COLUMN isdeleted TYPE isdeleted  USING (isdeleted::isdeleted)");
        DB::statement("ALTER TABLE website_reviews ALTER COLUMN isdeleted SET DEFAULT isdeleted '0'");
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
