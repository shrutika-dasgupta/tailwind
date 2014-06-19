<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDataTrafficDailyCountsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('data_traffic_daily_counts', function($table) {

            $table->integer('traffic_id');
            $table->integer('date');
            $table->string('device', 30);
            $table->string('network', 30);
            $table->string('country', 100);
            $table->string('region', 200);
            $table->integer('users');
            $table->integer('new_users');
            $table->integer('sessions');
            $table->integer('bounces');
            $table->integer('time_on_site');
            $table->integer('pageviews');
            $table->integer('pageviews_per_session');
            $table->integer('unique_pageviews');
            $table->integer('transactions');
            $table->integer('revenue');
            $table->integer('added_at');
            $table->integer('timestamp');

            $table->primary(['traffic_id', 'date', 'device',
                            'network', 'country', 'region'], 'primay_key');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('data_traffic_daily_counts');
	}

}
