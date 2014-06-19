<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatusFeedTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('status_feeds', function($table) {
            $table->increments('id');
            $table->string('url')->unique();
            $table->integer('subscribers_count')->nullable();
            $table->integer('velocity')->nullable();
            $table->integer('engagement')->nullable();
            $table->string('language')->nullable();
            $table->integer('last_pulled')->nullable();
            $table->integer('added_at');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('status_feeds');
	}

}
