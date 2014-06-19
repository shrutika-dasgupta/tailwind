<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCuratedToDataFeedEntries extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('data_feed_entries', function(Blueprint $table)
		{
			$table->tinyInteger('curated')->after('published_at');
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
			$table->dropColumn('curated');
		});
	}
}
