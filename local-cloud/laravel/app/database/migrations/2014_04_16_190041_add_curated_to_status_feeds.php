<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCuratedToStatusFeeds extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('status_feeds', function(Blueprint $table)
		{
			$table->tinyInteger('curated')->after('engagement');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('status_feeds', function(Blueprint $table)
		{
			$table->dropColumn('curated');
		});
	}

}
