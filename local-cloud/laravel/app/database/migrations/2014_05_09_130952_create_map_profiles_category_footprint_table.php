<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMapProfilesCategoryFootprintTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{

        Schema::create('map_profiles_category_footprint', function($table)
        {
            $table->engine = 'InnoDB';

            $table->bigInteger('user_id');
            $table->string('activity_indv_hash');
            $table->string('influence_indv_hash');
            $table->string('board_indv_count_hash');
            $table->string('activity_collab_hash');
            $table->string('influence_collab_hash');
            $table->string('board_collab_count_hash');
            $table->string('recency_hash');
            $table->string('footprint_hash');
            $table->integer('timestamp');
	    });
    }

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('map_profiles_category_footprint');
	}

}
