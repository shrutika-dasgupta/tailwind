<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTrackTypeToStatusFootprintTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('status_footprint', function(Blueprint $table) {
			$table->string('track_type', 100)->after('user_id');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('status_footprint', function(Blueprint $table) {
			$table->dropColumn('track_type');
		});
		//
	}

}
