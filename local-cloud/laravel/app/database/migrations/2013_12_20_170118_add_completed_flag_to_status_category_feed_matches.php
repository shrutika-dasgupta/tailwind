<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCompletedFlagToStatusCategoryFeedMatches extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('status_category_feed_matches', function(Blueprint $table)
		{
            $table->integer('completed_flag')->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('status_category_feed_matches', function(Blueprint $table)
		{
            $table->dropColumn('completed_flag');
		});
	}

}