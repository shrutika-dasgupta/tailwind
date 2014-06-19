<?php

use Illuminate\Database\Migrations\Migration;

class CreateDataBoardFollowers extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
    public function up()
    {
        Schema::create('data_board_followers', function($table){

            $table->bigInteger('user_id');
            $table->bigInteger('board_id');
            $table->bigInteger('follower_user_id');
            $table->integer('follower_pin_count');
            $table->integer('follower_follower_count');
            $table->string('follower_gender', 20);
            $table->integer('follower_created_at');
            $table->integer('added_at');
            $table->integer('updated_at');

            $table->primary(array('user_id', 'board_id', 'follower_user_id'));
            $table->index('follower_user_id');
            $table->index('board_id');
            $table->index('added_at');
        });
    }


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('data_board_followers');
	}

}
