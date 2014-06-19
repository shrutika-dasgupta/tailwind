<?php

use Illuminate\Database\Migrations\Migration;

class CreateStatusFootprintTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('status_footprint', function($table)
        {
            $table->engine = 'InnoDB';

            $table->bigInteger('user_id');
            $table->integer('last_run');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('status_footprint');
	}

}