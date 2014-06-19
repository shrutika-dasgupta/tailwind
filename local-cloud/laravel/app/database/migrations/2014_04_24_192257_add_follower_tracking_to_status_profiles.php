<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFollowerTrackingToStatusProfiles extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('status_profiles', function(Blueprint $table)
		{
			$table->tinyInteger('followers_found')->after('last_pulled_boards');
			$table->tinyInteger('last_updated_followers_found')->after('last_pulled_boards');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('status_profiles', function(Blueprint $table)
		{
			$table->dropColumn('followers_found');
			$table->dropColumn('last_updated_followers_found');
		});
	}
}
