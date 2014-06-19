<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUploadedPinsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('publisher_uploaded_posts', function(Blueprint $table)
        {
            $table->integer('id',true);
            $table->integer('account_id');
            $table->string('type',255)->default('local');
            $table->string('location',255);
            $table->string('status')->default('C');
            $table->integer('added_at');
            $table->integer('updated_at');

        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('publisher_uploaded_posts');
	}

}
