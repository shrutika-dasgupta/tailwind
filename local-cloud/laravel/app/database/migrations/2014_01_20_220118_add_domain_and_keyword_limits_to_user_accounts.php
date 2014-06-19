<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDomainAndKeywordLimitsToUserAccounts extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('user_accounts', function(Blueprint $table)
		{
            $table->integer('domain_limit')->default(0)->after('chargify_id_alt');
            $table->integer('keyword_limit')->default(0)->after('chargify_id_alt');
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
            $table->dropColumn('domain_limit');
            $table->dropColumn('keyword_limit');
		});
	}

}