<?php

use Illuminate\Database\Migrations\Migration;

class CreateCacheProfileFollowerDistribution extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
    public function up()
    {
        Schema::create('cache_profile_follower_distribution', function($table){

            $table->bigInteger('user_id');
            $table->integer('boards_followed');
            $table->integer('followers');

            $table->primary(array('user_id', 'boards_followed'));
        });
    }

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('cache_profile_follower_distribution');
	}

}