<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNextPullStatusTraffic extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('status_traffic', function(Blueprint $table) {

            $table->integer('next_pull')->after('last_calced');

        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('status_traffic', function(Blueprint $table) {

            $table->dropColumn('next_pull');

        });
	}

}
