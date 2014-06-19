<?php

use Illuminate\Database\Migrations\Migration;

class MapPinsPromoters extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('map_pins_promoters', function($table)
        {
            $table->engine = 'InnoDB';

            $table->integer('pin_id');
            $table->integer('promoter_id');
            $table->string('feed');
            $table->string('feed_attribute');
            $table->integer('found_at', false);

            $table->index('pin_id');
            $table->index('promoter_id');
            $table->primary(array('pin_id','promoter_id'));

        });

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('map_pins_promoters');
	}

}