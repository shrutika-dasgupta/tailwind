<?php

use Illuminate\Database\Migrations\Migration;

class UserEmailsAddresses extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_emails_addresses');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_emails_addresses', function ($table) {
            /** @var $table \Illuminate\Database\Schema\Blueprint */
            $table->engine = 'InnoDB';

            $table->integer('email_id');
            $table->string('address', 200);
            $table->string('type', 100);
            $table->integer('created_at');
            $table->integer('updated_at');

        });
    }

}