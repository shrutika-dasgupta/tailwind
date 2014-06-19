<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMetaFieldsToStatusBoards extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('status_boards', function(Blueprint $table)
		{
            $table->integer('collaborator_count')->after('is_collaborator');
            $table->string('category',30)->after('is_collaborator');
            $table->string('layout',25)->after('is_collaborator');
            $table->integer('created_at')->after('is_collaborator');
            $table->integer('follower_count')->after('is_collaborator');
            $table->integer('pin_count')->after('is_collaborator');
            $table->integer('added_at')->after('is_collaborator');
            $table->integer('updated_at')->after('is_collaborator');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('status_boards', function(Blueprint $table)
		{
            $table->dropColumn('collaborator_count');
            $table->dropColumn('category');
            $table->dropColumn('layout');
            $table->dropColumn('created_at');
            $table->dropColumn('follower_count');
            $table->dropColumn('pin_count');
            $table->dropColumn('added_at');
            $table->dropColumn('updated_at');
		});
	}

}
