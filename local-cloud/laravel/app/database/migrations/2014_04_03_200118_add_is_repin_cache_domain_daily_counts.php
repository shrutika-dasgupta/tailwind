<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsRepinCacheDomainDailyCounts extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('cache_domain_daily_counts', function(Blueprint $table)
		{
            $table->integer('is_repin')->after('date');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('cache_domain_daily_counts', function(Blueprint $table)
		{
            $table->dropColumn('is_repin');
		});
	}

}