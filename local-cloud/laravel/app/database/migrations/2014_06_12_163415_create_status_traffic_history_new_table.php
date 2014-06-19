<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatusTrafficHistoryNewTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('status_traffic_history_new', function($table) {
            $table->integer('traffic_id');
            $table->integer('date');
            $table->integer('timestamp');
        });


	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('status_traffic_history_new');
	}

}
