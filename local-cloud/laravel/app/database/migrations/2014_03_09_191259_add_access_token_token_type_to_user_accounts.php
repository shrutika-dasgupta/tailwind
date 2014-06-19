<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAccessTokenTokenTypeToUserAccounts extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('user_accounts', function(Blueprint $table)
		{
            $table->string('access_token', 150);
            $table->string('token_type', 30);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('user_accounts', function(Blueprint $table)
        {
            $table->dropColumn('access_token');
            $table->dropColumn('token_type');

        });
	}

}
