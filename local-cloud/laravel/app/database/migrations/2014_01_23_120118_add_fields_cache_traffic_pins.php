<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldsCacheTrafficPins extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('cache_traffic_pins', function(Blueprint $table)
		{
            $table->string('method', 20)->after('board_id');
            $table->bigInteger('via_pinner')->after('parent_pin');
            $table->bigInteger('origin_pin')->after('parent_pin');
            $table->bigInteger('origin_pinner')->after('parent_pin');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('cache_traffic_pins', function(Blueprint $table)
		{
            $table->dropColumn('method');
            $table->dropColumn('via_pinner');
            $table->dropColumn('origin_pin');
            $table->dropColumn('origin_pinner');
		});
	}

}