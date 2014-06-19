<?php

use Illuminate\Database\Migrations\Migration;

class CreateStatusCategoryFeedQueueTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('status_category_feed_queue', function($table)
        {
            $table->engine = 'InnoDB';

            $table->bigInteger('pin_id');
            $table->bigInteger('user_id');
            $table->bigInteger('board_id');
            $table->string('domain');
            $table->string('method');
            $table->smallInteger('is_repin');
            $table->integer('parent_pin');
            $table->integer('via_pinner');
            $table->integer('origin_pin');
            $table->integer('origin_pinner');
            $table->string('image_url');
            $table->string('image_square_url');
            $table->string('link');
            $table->string('description');
            $table->string('location');
            $table->string('dominant_color');
            $table->string('rich_product');
            $table->integer('repin_count');
            $table->integer('like_count');
            $table->integer('comment_count');
            $table->integer('created_at');
            $table->string('category_name');
            $table->string('match_type');
            $table->integer('timestamp');

            $table->primary(array('pin_id'));
        });
    }

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('status_category_feed_queue');
	}

}
