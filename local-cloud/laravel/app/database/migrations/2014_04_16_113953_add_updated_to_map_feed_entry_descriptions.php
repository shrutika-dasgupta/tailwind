<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUpdatedToMapFeedEntryDescriptions extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('map_feed_entry_descriptions', function(Blueprint $table)
		{
			$table->integer('updated_at');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('map_feed_entry_descriptions', function(Blueprint $table)
		{
			$table->dropColumn('updated_at');
		});
	}

}
