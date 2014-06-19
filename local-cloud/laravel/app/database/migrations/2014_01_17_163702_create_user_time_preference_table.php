<?php

use Illuminate\Database\Migrations\Migration;

class CreateUserTimePreferenceTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('publisher_time_slots', function($table)
        {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('day_preference');
            $table->string('time_preference', 8);
            $table->integer('pin_uuid')->nullable();
            $table->integer('send_at')->nullable();
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('publisher_time_slots');
	}

}
