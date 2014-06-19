<?php

use Illuminate\Database\Migrations\Migration;

class AddPinterestGender extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{

        Schema::table('data_profiles_new', function ($table) {

            /** @var $table /Table */
            $table->string('p_gender',50)
            ->nullable()
            ->default(NULL)
            ->after('gender');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		//
        Schema::table('data_profiles_new', function ($table) {
            $table->dropColumn('p_gender');
        });
	}

}