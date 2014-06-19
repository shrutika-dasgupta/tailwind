<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLastPulledSocialToDataFeedEntries extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('data_feed_entries', function(Blueprint $table)
		{
			$table->tinyInteger('last_pulled_social')->after('curated');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('data_feed_entries', function(Blueprint $table)
		{
			$table->dropColumn('last_pulled_social');
		});
	}

}
