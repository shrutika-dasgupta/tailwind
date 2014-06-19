<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEngagementToDataFeedEntries extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('data_feed_entries', function(Blueprint $table)
		{
			$table->integer('engagement')->after('description')->nullable();
			$table->double('engagement_rate')->after('engagement')->nullable();
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
			$table->dropColumn('engagement');
			$table->dropColumn('engagement_rate');
		});
	}

}
