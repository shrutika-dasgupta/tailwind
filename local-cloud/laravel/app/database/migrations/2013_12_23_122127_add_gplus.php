<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGplus extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('data_profiles_new', function(Blueprint $table)
		{
            $table->string('google_plus_url',50);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('data_profiles_new', function(Blueprint $table)
		{
            $table->dropColumn('google_plus_url');
		});
	}

}