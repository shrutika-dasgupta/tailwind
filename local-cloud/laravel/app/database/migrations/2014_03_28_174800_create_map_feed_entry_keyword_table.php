<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMapFeedEntryKeywordTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('map_feed_entry_keywords', function(Blueprint $table)
        {
            $table->integer('entry_id');
            $table->string('keyword');
            $table->integer('added_at');

        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('map_feed_entry_keywords');
	}

}
