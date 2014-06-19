<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCalculateInfluencersFootprintToStatusProfilesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('status_profiles', function ($table) {
	        $table->integer('calculate_influencers_footprint')->after('last_updated_followers_found');
        });
    }

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('status_profiles', function ($table) {
           $table->dropColumn('calculate_influencers_footprint');
        });
	}

}
