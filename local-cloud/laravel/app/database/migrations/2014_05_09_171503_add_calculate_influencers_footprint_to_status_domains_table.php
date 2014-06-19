<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCalculateInfluencersFootprintToStatusDomainsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('status_domains', function ($table) {
            $table->integer('calculate_influencers_footprint')->after('last_calced');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('status_domains', function ($table) {
            $table->dropColumn('calculate_influencers_footprint');
        });
	}

}
