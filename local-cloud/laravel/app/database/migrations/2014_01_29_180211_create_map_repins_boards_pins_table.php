<?php

use Illuminate\Database\Migrations\Migration;

class CreateMapRepinsBoardsPinsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('map_repins_boards_pins', function($table){
            $table->engine = 'InnoDB';

            $table->bigInteger('board_id');
            $table->bigInteger('parent_pin');
            $table->bigInteger('origin_pin');
            $table->boolean('flag');
            $table->integer('added_at');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('map_repins_boards_pins');
	}

}
