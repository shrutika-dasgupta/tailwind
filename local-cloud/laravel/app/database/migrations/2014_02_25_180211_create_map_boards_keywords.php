<?php

use Illuminate\Database\Migrations\Migration;

class CreateMapBoardsKeywords extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('map_boards_keywords', function($table){

            $table->bigInteger('board_id');
            $table->string('keyword', 100);
            $table->integer('pin_count');
            $table->integer('follower_count');
            $table->string('category', 50);
            $table->integer('first_found_at');
            $table->integer('times_found');
            $table->integer('last_pulled_pins');
            $table->integer('pin_matches_found');
            $table->integer('timestamp');

            $table->primary(array('board_id', 'keyword'));
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('map_boards_keywords');
	}

}
