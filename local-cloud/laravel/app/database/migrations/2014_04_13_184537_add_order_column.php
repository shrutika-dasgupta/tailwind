<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrderColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        DB::statement('ALTER TABLE publisher_posts ADD `order` MEDIUMINT UNSIGNED ZEROFILL NOT NULL');

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		//
        Schema::table('publisher_posts', function(Blueprint $table)
        {
            $table->dropColumn('order');
        });
	}

}
