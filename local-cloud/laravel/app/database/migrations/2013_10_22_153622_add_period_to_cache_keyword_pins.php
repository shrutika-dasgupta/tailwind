<?php

use Illuminate\Database\Migrations\Migration;

class AddPeriodToCacheKeywordPins extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('cache_keyword_pins', function ($table) {
            $table->integer('period')->after('keyword');
            $table->dropPrimary(array('keyword','pin_id'));
            $table->primary(array('keyword','period','pin_id'));
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('cache_keyword_pins', function ($table) {
            $table->dropColumn('period');
            $table->dropPrimary(array('keyword','period','pin_id'));
            $table->primary(array('keyword','pin_id'));
        });
	}

}