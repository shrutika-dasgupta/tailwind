<?php

use Illuminate\Database\Migrations\Migration;

class CreateStatusCategoryFeedMatchesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('status_category_feed_matches', function($table)
            {
                $table->engine = 'InnoDB';

                $table->bigInteger('pin_id');
                $table->bigInteger('user_id');

                $table->string('domain');
                $table->bigInteger('via_pinner');
                $table->bigInteger('origin_pinner');
                $table->string('description');
                $table->string('match_type');
                $table->string('category_name');
                $table->integer('timestamp');

                $table->primary(array('pin_id'));
            });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('status_category_feed_matches');
	}

}
