<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldsRunningStatusApiCallsQueue extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('status_api_calls_queue', function(Blueprint $table)
		{
            $table->tinyInteger('running')->after('track_type');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('status_api_calls_queue', function(Blueprint $table)
		{
            $table->dropColumn('running');
		});
	}

}
