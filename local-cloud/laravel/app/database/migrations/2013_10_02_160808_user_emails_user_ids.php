<?php

/**
 * This file was created using this cmd
 * php artisan migrate:make user_emails_user_ids
 */

use Illuminate\Database\Migrations\Migration;

class UserEmailsUserIds extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_emails_user_ids');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_emails_user_ids', function ($table) {
            /** @var $table \Illuminate\Database\Schema\Blueprint */
            $table->engine = 'InnoDB';

            $table->integer('email_id');
            $table->integer('created_at');
            $table->integer('updated_at');

        });

        DB::statement('
                ALTER TABLE `user_emails_user_ids`
                ADD `user_id` BIGINT(20)  NULL  DEFAULT NULL
                AFTER `email_id`

        ');
    }

}