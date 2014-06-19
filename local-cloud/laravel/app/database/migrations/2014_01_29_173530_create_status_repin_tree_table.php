<?php

use Illuminate\Database\Migrations\Migration;

class CreateStatusRepinTreeTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('status_repin_tree', function($table){
            $table->engine = 'InnoDB';

            $table->bigInteger('pin_id');
            $table->bigInteger('source_pin');
            $table->bigInteger('parent_pin');
            $table->bigInteger('origin_pin');
            $table->integer('has_repins');
            $table->integer('last_pulled_repins');
            $table->integer('last_pulled_boards');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('status_repin_tree');
	}

}