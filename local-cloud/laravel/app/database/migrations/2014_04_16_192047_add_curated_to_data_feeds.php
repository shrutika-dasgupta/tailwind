<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCuratedToDataFeeds extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('data_feeds', function(Blueprint $table)
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
		Schema::table('data_feeds', function(Blueprint $table)
		{
			$table->dropColumn('curated');
		});
	}
}
