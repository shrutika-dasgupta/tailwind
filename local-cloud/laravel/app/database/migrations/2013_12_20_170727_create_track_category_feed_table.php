<?php

use Illuminate\Database\Migrations\Migration;

class CreateTrackCategoryFeedTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('track_category_consume', function($table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->integer('timestamp')->default(NULL);
            $table->integer('pin_count')->default(NULL);
            $table->string('category_name', 50);
            $table->float('pull_from_queue_time');
            $table->float('keyword_check_time');
            $table->integer('keyword_match_count')->default(NULL);
            $table->float('user_check_time');
            $table->integer('users_match_count')->default(NULL);
            $table->float('domain_check_time');
            $table->integer('domain_match_count')->default(NULL);
            $table->float('insert_matches');
            $table->float('insert_range_user');
            $table->float('insert_range_domain');
            $table->float('insert_range_keyword');
            $table->float('user_id_inner_time');
            $table->float('via_pinner_inner_time');
            $table->float('origin_pinner_inner_time');
            $table->float('user_hash_inner_time');

        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
            Schema::drop('track_category_consume');
	}

}