<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveLanguageFromStatusFeeds extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('status_feeds', function(Blueprint $table)
		{
			$table->dropColumn('language');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('status_feeds', function(Blueprint $table)
		{
			$table->string('language')->nullable();
		});
	}

}
