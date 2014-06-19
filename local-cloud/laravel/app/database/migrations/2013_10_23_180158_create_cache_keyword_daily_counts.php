<?php

use Illuminate\Database\Migrations\Migration;

class CreateCacheKeywordDailyCounts extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('cache_keyword_daily_counts', function($table)
        {
            $table->engine = 'InnoDB';

            $table->string('keyword',100);
            $table->integer('date');
            $table->integer('pin_count');
            $table->integer('pinner_count');
            $table->integer('repin_count');
            $table->integer('like_count');
            $table->integer('comment_count');
            $table->integer('reach');

            $table->primary(array('keyword','date'));
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('cache_keyword_daily_counts');
	}

}