<?php

use Illuminate\Database\Migrations\Migration;

class UpdateEmailPreferences extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_email_preferences', function ($table) {
            $table->dropColumn('user_id');
        });

        Schema::table('user_email_attachment_preferences', function ($table) {
            $table->dropColumn('user_id');
        });
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('
                ALTER TABLE `user_email_preferences`
                ADD `user_id` BIGINT(20)  NULL  DEFAULT NULL
                AFTER `username`

        ');

        DB::statement('
                ALTER TABLE `user_email_attachment_preferences`
                ADD `user_id` BIGINT(20)  NULL  DEFAULT NULL
                AFTER `username`

        ');
    }

}