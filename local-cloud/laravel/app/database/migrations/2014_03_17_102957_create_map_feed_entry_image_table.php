<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMapFeedEntryImageTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('map_feed_entry_images', function($table) {
            $table->integer('feed_entry_id');
			$table->string('url', 200);
			$table->smallInteger('width');
			$table->smallInteger('height');
			$table->boolean('primary');
			$table->integer('added_at');
            $table->integer('updated_at');

			$table->primary(array('feed_entry_id', 'url'));
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('map_feed_entry_images');
	}

}
