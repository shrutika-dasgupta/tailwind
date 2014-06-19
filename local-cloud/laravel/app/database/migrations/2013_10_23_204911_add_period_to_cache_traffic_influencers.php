<?php

use Illuminate\Database\Migrations\Migration;

class AddPeriodToCacheTrafficInfluencers extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('cache_traffic_influencers', function ($table) {
            $table->integer('period')->after('traffic_id');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('cache_traffic_influencers', function ($table) {
            $table->dropPrimary();
            $table->dropColumn('period');
            $table->primary(array('traffic_id', 'influencer_user_id'));
        });
	}

}
