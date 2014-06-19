<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldsLastPulledBoardsStatusKeywords extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('status_keywords', function(Blueprint $table)
		{
            $table->integer('last_pulled_boards')->after('last_pulled');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('status_keywords', function(Blueprint $table)
		{
            $table->dropColumn('last_pulled_boards');
		});
	}

}