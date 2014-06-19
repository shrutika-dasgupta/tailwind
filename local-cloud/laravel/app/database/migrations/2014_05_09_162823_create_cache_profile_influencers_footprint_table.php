<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCacheProfileInfluencersFootprintTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('cache_profile_influencers_footprint', function($table)
        {
            $table->engine = 'InnoDB';

            $table->bigInteger('user_id');
            $table->string('category', 100);
            $table->integer('footprint_count');

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
        Schema::drop('cache_profile_influencers_footprint');
	}

}
