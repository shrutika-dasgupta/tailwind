<?php

use Illuminate\Database\Migrations\Migration;

class CreateCategoryFootprintTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('category_footprint', function($table)
		{
			$table->engine = 'InnoDB';

			$table->integer('user_id');
			$table->string('activity_indv_hash', 200);
			$table->string('influence_indv_hash', 200);
			$table->string('board_indv_count_hash', 200);
			$table->string('activity_collab_hash', 200);
			$table->string('influence_collab_hash', 200);
			$table->string('board_collab_count_hash', 200);
			$table->string('recency_hash', 200);
			$table->string('footprint_hash', 200);
            $table->integer('timestamp');

            $table->primary(array('user_id'));
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('category_footprint');
	}

}