<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOwnedFlagsToStatusBoards extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('status_boards', function(Blueprint $table)
		{
            $table->integer('followers_found')->after('last_pulled_followers');
            $table->integer('last_updated_followers_found')->after('last_pulled_followers');
            $table->tinyInteger('is_owned')->after('track_type');
            $table->tinyInteger('is_collaborator')->after('track_type');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('status_boards', function(Blueprint $table)
		{
            $table->dropColumn('followers_found');
            $table->dropColumn('last_updated_followers_found');
            $table->dropColumn('is_owned');
            $table->dropColumn('is_collaborator');
		});
	}

}
