<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveLegacyUserFeaturePermissionsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::create('user_feature_permissions', function(Blueprint $table)
        {
            $table->increments('org_id');
            $table->integer('is_enabled');
            $table->integer('limit')->nullable();
        });
		//
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::drop('user_feature_permissions');
	}

}
