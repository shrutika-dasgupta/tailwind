<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUsersTimezoneColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('users', function(Blueprint $table)
		{
            DB::statement("
                ALTER TABLE `users`
                MODIFY `timezone` VARCHAR(40) NULL DEFAULT 'America/New_York'
            ");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('users', function(Blueprint $table)
		{
            DB::statement("
                ALTER TABLE `users`
                MODIFY `timezone` VARCHAR(11) NULL DEFAULT '-5:00'
            ");
		});
	}

}