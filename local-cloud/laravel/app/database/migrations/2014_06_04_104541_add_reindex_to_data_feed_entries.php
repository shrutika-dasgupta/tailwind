<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReindexToDataFeedEntries extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('data_feed_entries', function(Blueprint $table)
		{
			$table->boolean('reindex')
				  ->nullable()
				  ->default(NULL)
				  ->after('curated');

			$table->index('reindex', 'reindex_idx');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('data_feed_entries', function(Blueprint $table)
		{
			$table->dropColumn('reindex');
		});
	}

}
