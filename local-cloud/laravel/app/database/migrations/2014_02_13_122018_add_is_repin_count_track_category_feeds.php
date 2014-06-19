<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsRepinCountTrackCategoryFeeds extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('track_category_feeds', function(Blueprint $table)
		{
            $table->integer('new_recent_pins_count')->after('new_pins_count');
            $table->integer('new_is_repin_count')->after('new_pins_count');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('track_category_feeds', function(Blueprint $table)
		{
            $table->dropColumn('new_recent_pins_count');
            $table->dropColumn('new_is_repin_count');
		});
	}

}