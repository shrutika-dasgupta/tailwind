<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMapFeedDtagsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('map_feed_dtags', function(Blueprint $table)
        {
            $table->integer('feed_id');
            $table->string('related_dtag');
            $table->integer('added_at');

            $table->primary(array('feed_id',
                                  'related_dtag'));

        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('map_feed_dtags');
	}

}
