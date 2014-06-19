<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPullFlagsToDataFeedEntries extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('data_feed_entries', function (Blueprint $table) {
            $table->integer('last_pulled_fb');
            $table->integer('last_pulled_twitter');

            $table->dropColumn('last_pulled_social');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('data_feed_entries', function (Blueprint $table) {
            $table->dropColumn('last_pulled_fb');
            $table->dropColumn('last_pulled_twitter');

            $table->integer('last_pulled_social');
        });
	}

}
