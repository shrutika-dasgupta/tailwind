<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableMapFeedWotCategories extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('map_feed_wot_categories', function($table) {
            $table->integer("feed_id");
            $table->tinyInteger("curated");
            $table->integer("category_identifier");
            $table->integer("reliability_score");
            $table->integer("added_at");
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('map_feed_wot_categories');
	}

}
