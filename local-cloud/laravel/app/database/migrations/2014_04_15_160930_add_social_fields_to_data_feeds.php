<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSocialFieldsToDataFeeds extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('data_feeds', function(Blueprint $table)
		{
			$table->string('facebook_username', 100)->after('fb_likes')->nullable();
			$table->string('twitter_username', 100)->after('facebook_username')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('data_feeds', function(Blueprint $table)
		{
			$table->dropColumn('facebook_username');
			$table->dropColumn('twitter_username');
		});
	}

}
