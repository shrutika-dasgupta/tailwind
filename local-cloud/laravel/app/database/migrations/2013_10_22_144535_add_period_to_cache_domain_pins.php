<?php

use Illuminate\Database\Migrations\Migration;

class AddPeriodToCacheDomainPins extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('cache_domain_pins', function ($table) {
            $table->integer('period')->after('domain');
            $table->dropPrimary(array('domain','pin_id'));
            $table->primary(array('domain','period','pin_id'));
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('cache_domain_pins', function ($table) {
            $table->dropColumn('period');
            $table->dropPrimary(array('domain','period','pin_id'));
            $table->primary(array('domain','pin_id'));
        });
	}

}