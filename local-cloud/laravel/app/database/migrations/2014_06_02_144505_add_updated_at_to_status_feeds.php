<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUpdatedAtToStatusFeeds extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('status_feeds', function(Blueprint $table)
		{
			$table->integer('updated_at')->after('added_at');
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
			$table->dropColumn('updated_at');
		});
	}

}
