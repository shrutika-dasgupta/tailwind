<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLastUpdatedToStatusApiCallTypesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('status_api_call_types', function(Blueprint $table) {
			$table->integer('last_updated');
        });
		//
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('status_api_call_types', function(Blueprint $table) {
			$table->dropColumn('last_updated');
		});
	}

}
