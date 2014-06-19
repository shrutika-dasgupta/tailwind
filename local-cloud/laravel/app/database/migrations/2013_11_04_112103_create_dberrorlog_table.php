<?php

use Illuminate\Database\Migrations\Migration;

class CreateDberrorlogTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('db_error_log', function($table)
          {
                $table->engine = 'InnoDB';


                $table->increments('id');
                $table->string('script_name', 20);
                $table->integer('line_number');
                $table->string('sqlstate_error_code', 20);
                $table->integer('driver_specific_error_code');
                $table->string('driver_specific_error_message', 100);
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
            Schema::drop('db_error_log');
	}

}
