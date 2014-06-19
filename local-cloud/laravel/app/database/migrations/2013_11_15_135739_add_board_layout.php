<?php

use Illuminate\Database\Migrations\Migration;

class AddBoardLayout extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('data_boards', function ($table) {

            /** @var $table /Table */
            $table->string('layout',50)
                  ->nullable()
                  ->default(NULL)
                  ->after('category');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('data_boards', function ($table) {
            $table->dropColumn('layout');
        });
	}

}