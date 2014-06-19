<?php

use Illuminate\Database\Migrations\Migration;

class AddPeriodToCacheTrafficPins extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('cache_traffic_pins', function ($table) {
            $table->integer('period')->after('traffic_id');
            $table->dropPrimary(array('traffic_id','pin_id'));
            $table->primary(array('traffic_id','period','pin_id'));
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('cache_traffic_pins', function ($table) {
            $table->dropColumn('period');
            $table->dropPrimary(array('traffic_id','period','pin_id'));
            $table->primary(array('traffic_id','pin_id'));
        });
	}

}