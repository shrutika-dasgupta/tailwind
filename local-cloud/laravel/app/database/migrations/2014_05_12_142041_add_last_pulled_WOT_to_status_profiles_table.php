<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLastPulledWOTToStatusProfilesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('status_feeds', function(Blueprint $table)
		{
			$table->integer('last_pulled_WOT')->after('last_pulled');
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
			$table->dropColumn('last_pulled_WOT');
		});
	}

}
