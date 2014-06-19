<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserProperties extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('user_properties', function(Blueprint $table)
		{
            $table->bigInteger('cust_id');
            $table->string('property');
            $table->integer('count');
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->index(
                array(
                     'cust_id'
                )
            );

            $table->primary(array('cust_id','property'));
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('user_properties');
	}

}
