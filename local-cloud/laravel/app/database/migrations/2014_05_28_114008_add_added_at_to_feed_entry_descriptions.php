<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAddedAtToFeedEntryDescriptions extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('map_feed_entry_descriptions', function(Blueprint $table)
		{
			$table->integer('added_at')->after('description');
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
			$table->dropColumn('added_at');
		});
	}

}
