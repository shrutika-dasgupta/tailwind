<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDataFeedsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('data_feeds', function($table) {
            $table->integer('feed_id');
            $table->string('url');
            $table->string('domain', 100);
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->integer('subscribers_count')->nullable();
            $table->integer('velocity')->nullable();
            $table->integer('twitter_followers')->nullable();
            $table->integer('fb_likes')->nullable();
            $table->string('language')->nullable();
            $table->integer('engagement')->nullable();
            $table->integer('timestamp');

            $table->primary(array('feed_id'));
        });
    }

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('data_feeds');
	}

}
