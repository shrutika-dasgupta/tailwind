<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMapProfilesCategoryHashTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('map_profiles_category_hash', function($table)
        {
            $table->engine = 'InnoDB';

            $table->bigInteger('user_id');
            $table->string("category");
            $table->integer("footprint_count");
            $table->integer("activity_count");
            $table->integer("influence_count");
            $table->integer("board_count");
            $table->integer("recency_order");

           $table->primary(['user_id', 'category']);

        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('map_profiles_category_hash');
	}

}
