<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEngagementMapPinsKeywords extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('map_pins_keywords', function(Blueprint $table)
		{
            $table->integer('repin_count')->after('domain');
            $table->integer('like_count')->after('domain');
            $table->integer('comment_count')->after('domain');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('map_pins_keywords', function(Blueprint $table)
		{
            $table->dropColumn('repin_count');
            $table->dropColumn('like_count');
            $table->dropColumn('comment_count');
		});
	}

}