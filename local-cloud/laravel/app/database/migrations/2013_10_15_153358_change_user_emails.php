<?php

use Illuminate\Database\Migrations\Migration;

class ChangeUserEmails extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_email_queue', function ($table) {
            $table->string('username', 200);
        });
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_email_queue', function ($table) {
            $table->dropColumn('username');
        });

    }

}