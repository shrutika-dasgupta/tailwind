<?php

use Illuminate\Database\Migrations\Migration;

class CreateUserSchedulePinsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('publisher_posts', function($table)
        {
            $table->increments('id');
            $table->integer('account_id');
            $table->string('board_name', 50);
            $table->string('domain', 100);
            $table->string('image_url', 200);
            $table->string('link', 200);
            $table->string('description', 255);
            $table->bigInteger('parent_pin')->nullable();
            $table->bigInteger('pin_id')->nullable();
            $table->integer('added_at');
            $table->integer('sent_at')->nullable();

        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('publisher_posts');
	}

}
