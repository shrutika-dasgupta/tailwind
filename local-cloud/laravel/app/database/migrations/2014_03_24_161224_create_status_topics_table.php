<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatusTopicsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('status_topics', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('topic')->unique();
			$table->string('type', 20);
            $table->tinyInteger('curated');
			$table->integer('last_pulled');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('status_topics');
	}

}
