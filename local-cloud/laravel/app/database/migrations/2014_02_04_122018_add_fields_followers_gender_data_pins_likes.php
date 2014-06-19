<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldsFollowersGenderDataPinsLikes extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('data_pins_likes', function(Blueprint $table)
		{
            $table->integer('follower_count')->after('liker_user_id');
            $table->string('gender', 12)->after('liker_user_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('data_pins_likes', function(Blueprint $table)
		{
            $table->dropColumn('follower_count');
            $table->dropColumn('gender');
		});
	}

}