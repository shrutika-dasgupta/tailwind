<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDataTrafficNewTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('data_traffic_new', function($table) {
            $table->integer('traffic_id');
            $table->integer('hour');
            $table->string('device', 30);
            $table->string('network', 30);
            $table->integer('users');
            $table->integer('new_users');
            $table->integer('sessions');
            $table->integer('bounces');
            $table->integer('time_on_site');
            $table->integer('pageviews');
            $table->integer('pageviews_per_session');
            $table->integer('unique_pageviews');
            $table->integer('transactions');
            $table->double('revenue');
            $table->integer('timestamp');

            $table->primary(['traffic_id', 'hour', 'device', 'network']);

        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('data_traffic_new');
	}

}
