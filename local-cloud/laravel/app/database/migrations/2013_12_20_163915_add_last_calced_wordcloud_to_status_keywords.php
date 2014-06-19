<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLastCalcedWordcloudToStatusKeywords extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('status_keywords', function($table)
		{
            $table->integer('last_calced_wordcloud')->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('status_keywords', function($table)
		{
            $table->dropColumn('last_calced_wordcloud');
		});
	}

}