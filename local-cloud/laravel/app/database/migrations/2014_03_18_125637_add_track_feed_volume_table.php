<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTrackFeedVolumeTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('track_feed_volume', function($table) {
            $table->integer('feed_id');
            $table->integer('hours_since_last_run');
            $table->integer('new_entries_count');
             $table->integer('average_entries_per_hour');
             $table->integer('timestamp');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('track_feed_volume');
	}

}
