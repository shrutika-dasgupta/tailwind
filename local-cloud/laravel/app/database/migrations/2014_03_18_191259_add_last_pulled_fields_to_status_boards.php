<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLastPulledFieldsToStatusBoards extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('status_boards', function(Blueprint $table)
		{
            $table->bigInteger('owner_user_id')->after('board_id');
            $table->integer('last_pulled_pins');
            $table->integer('last_pulled_followers');
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
            $table->dropColumn('owner_user_id');
            $table->dropColumn('last_pulled_pins');
            $table->dropColumn('last_pulled_followers');

        });
	}

}
