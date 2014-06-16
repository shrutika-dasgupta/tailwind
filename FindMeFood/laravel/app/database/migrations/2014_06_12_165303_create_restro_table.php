<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRestroTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('restraurants',function($table)
		{
			$table->increments('sno');
			$table->char('id',30);
			$table->unique('id');
			$table->char('name',50);
			$table->text('address',100);
			$table->float('distance');
			$table->char('categoriesId',30);
			$table->char('categoriesName',30);
			$table->float('rating');


		})
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('restaurants');
	}

}
