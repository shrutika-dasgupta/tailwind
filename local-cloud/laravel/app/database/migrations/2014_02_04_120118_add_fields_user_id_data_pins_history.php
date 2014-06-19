<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldsUserIdDataPinsHistory extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('data_pins_history', function(Blueprint $table)
		{
            $table->bigInteger('user_id')->after('pin_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('data_pins_history', function(Blueprint $table)
		{
            $table->dropColumn('user_id');
		});
	}

}