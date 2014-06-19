<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDomainCreatedAtIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('data_pins_new',function(Blueprint $table) {
           $table->index(['domain','created_at'],'domain_created_at_idx');
        });
		//
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('data_pins_new',function(Blueprint $table) {
            $table->dropIndex('domain_created_at_idx');
        });
	}

}
