<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatusApiCallTypes extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
    public function up()
    {
        Schema::create('status_api_call_types', function(Blueprint $table){

            $table->string('api_call',50);
            $table->string('track_type', 25);
            $table->tinyInteger('updating_flag');

            $table->primary(array('api_call', 'track_type'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('status_api_call_types');
    }

}