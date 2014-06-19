<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDataFeedEntriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('data_feed_entries', function($table) {
            $table->increments('id');
            $table->integer('feed_id')->index();
            $table->string('domain', 100);
            $table->string('url')->unique();
            $table->string('title', 200);
            $table->string('description', 255);
            $table->smallInteger('social_score');
            $table->smallInteger('facebook_score');
            $table->smallInteger('googleplus_score');
            $table->smallInteger('pinterest_score');
            $table->smallInteger('twitter_score');
            $table->integer('published_at');
            $table->integer('added_at');
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
        Schema::drop('data_feed_entries');
	}

}
