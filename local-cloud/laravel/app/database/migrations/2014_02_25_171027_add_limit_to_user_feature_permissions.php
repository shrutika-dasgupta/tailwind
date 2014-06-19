<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLimitToUserFeaturePermissions extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('user_feature_permissions', function(Blueprint $table)
		{
			$table->integer('limit')->nullable()->after('is_enabled');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('user_feature_permissions', function(Blueprint $table)
		{
			$table->dropColumn('limit');
		});
	}

}