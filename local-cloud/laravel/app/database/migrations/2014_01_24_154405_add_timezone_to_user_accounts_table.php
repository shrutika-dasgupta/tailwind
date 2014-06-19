<?php

use Illuminate\Database\Migrations\Migration;

class AddTimezoneToUserAccountsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('user_accounts', function ($table) {
            $table->string('timezone', 30);
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('user_accounts', function ($table) {
            $table->dropColumn('timezone');
        });
	}

}