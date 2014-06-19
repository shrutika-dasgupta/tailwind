<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVisualUrlToDataFeeds extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('data_feeds', function(Blueprint $table)
		{
			$table->string('visual_url', 255)->after('description')->nullable();
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
			$table->dropColumn('visual_url');
		});
	}

}
