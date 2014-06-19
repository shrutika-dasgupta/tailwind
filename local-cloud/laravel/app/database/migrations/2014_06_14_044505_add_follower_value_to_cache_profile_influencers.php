<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFollowerValueToCacheProfileInfluencers extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('cache_profile_influencers', function(Blueprint $table)
		{
			$table->integer('boards_followed')->after('following_count');
			$table->integer('value')->after('following_count');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('cache_profile_influencers', function(Blueprint $table)
		{
			$table->dropColumn('boards_followed');
			$table->dropColumn('value');
		});
	}

}
