<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFollowerReachToCalcsBoardHistory extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('calcs_board_history', function(Blueprint $table)
		{
			$table->integer('follower_reach')->after('followers');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('calcs_board_history', function(Blueprint $table)
		{
			$table->dropColumn('follower_reach');
		});
	}

}
