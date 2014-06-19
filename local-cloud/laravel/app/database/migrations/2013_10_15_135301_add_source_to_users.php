<?php

use Illuminate\Database\Migrations\Migration;

class AddSourceToUsers extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('users', function ($table) {
            $table->string('source',200);
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('users', function ($table) {
            $table->dropColumn('source');
        });
	}

}