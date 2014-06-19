<?php

use Illuminate\Database\Migrations\Migration;

class CreateCacheKeywordWordcloudsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('cache_keyword_wordclouds', function($table) {

            $table->engine = 'InnoDB';

            $table->integer('date')->default(0);
            $table->integer('timestamp')->default(NULL);
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
        Schema::table('cache_keyword_wordclouds', function($table) {
            $table->dropColumn('date');
            $table->dropColumn('timestamp');



        });
	}

}