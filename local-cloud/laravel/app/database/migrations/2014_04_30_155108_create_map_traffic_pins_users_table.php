<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMapTrafficPinsUsersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
    public function up()
    {
        Schema::create('map_traffic_pins_users', function(Blueprint $table){

            $table->bigInteger('pin_id');
            $table->bigInteger('user_id');
            $table->integer('timestamp');
            $table->integer('calced_flag');

            $table->primary(array('pin_id', 'user_id'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('map_traffic_pins_users');
    }

}