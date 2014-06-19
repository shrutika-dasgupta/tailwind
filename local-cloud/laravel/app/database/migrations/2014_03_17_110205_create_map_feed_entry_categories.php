<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMapFeedEntryCategories extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('map_feed_entry_categories', function($table) {
            $table->integer('feed_entry_id');
            $table->string('category', 100);
            $table->integer('added_at');

            $table->primary(array('feed_entry_id'));
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('map_feed_entry_categories');
	}

}
