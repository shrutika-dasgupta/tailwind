<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNetworkAndDeviceToDataTrafficPagesNewTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('data_traffic_pages_new', function(Blueprint $table) {

            $table->string('network', 30)->after('page');
            $table->string('device', 30)->after('page');

        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('data_traffic_pages_new', function(Blueprint $table) {
           $table->dropColumn('network');
           $table->dropColumn('device');
        });
	}

}
