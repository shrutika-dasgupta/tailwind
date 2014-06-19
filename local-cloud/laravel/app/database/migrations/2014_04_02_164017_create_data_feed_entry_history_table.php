<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDataFeedEntryHistoryTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('data_feed_entry_history', function(Blueprint $table)
		{
			$table->integer('feed_entry_id')->index();
			$table->integer('date');
			$table->smallInteger('social_score');
			$table->smallInteger('facebook_score');
			$table->smallInteger('googleplus_score');
			$table->smallInteger('pinterest_score');
			$table->smallInteger('twitter_score');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('data_feed_entry_history');
	}

}
