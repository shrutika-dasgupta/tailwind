<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDataTrafficPagesNewTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{

        Schema::create('data_traffic_pages_new', function($table) {

            $table->integer('traffic_id');
            $table->integer('date');
            $table->string('page', 200);
            $table->string('full_referrer', 200);
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
            $table->integer('timestamp');

            $table->primary(['page','traffic_id', 'date']);

        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('data_traffic_pages_new');
	}

}
