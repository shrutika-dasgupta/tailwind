<?php

use Illuminate\Database\Migrations\Migration;

class CreateCacheDomainDailyCounts extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('cache_domain_daily_counts', function($table)
        {
            $table->engine = 'InnoDB';

            $table->string('domain',100);
            $table->integer('date');
            $table->integer('pin_count');
            $table->integer('pinner_count');
            $table->integer('repin_count');
            $table->integer('like_count');
            $table->integer('comment_count');
            $table->integer('reach');

            $table->primary(array('domain','date'));
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('cache_domain_daily_counts');
	}

}