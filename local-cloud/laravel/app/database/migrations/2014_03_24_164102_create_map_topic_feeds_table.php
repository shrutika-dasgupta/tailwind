<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMapTopicFeedsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('map_topics_feeds', function(Blueprint $table)
		{
			$table->integer('topic_id');
			$table->integer('feed_id');
            $table->integer('score');
			$table->integer('added_at');
            $table->primary(array('topic_id',
                                  'feed_id'));
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('map_topics_feeds');
	}

}
