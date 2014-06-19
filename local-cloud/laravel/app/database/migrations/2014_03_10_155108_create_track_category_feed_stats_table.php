<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrackCategoryFeedStatsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
    public function up()
    {
        Schema::create('track_category_feed_stats', function($table){

            $table->string('category',50);
            $table->integer('hour');
            $table->string('recency', 8);
            $table->string('domain', 100);
            $table->integer('pin_count');
            $table->integer('repin_count');
            $table->integer('like_count');
            $table->integer('comment_count');

            $table->primary(array('category', 'hour', 'recency', 'domain'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('track_category_feed_stats');
    }

}