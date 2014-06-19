<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Migrates the table that stores groups of topics.
 * 
 * @author Daniel
 */
class CreateUserAccountsTagsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('user_accounts_tags', function(Blueprint $table)
		{
			$table->integer('account_id');
            $table->string('name', 100);
            $table->string('topic', 100);
			$table->integer('created_at');
			$table->integer('updated_at');

			$table->primary(array('account_id', 'name', 'topic'));
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('user_accounts_tags');
	}

}