<?php

use Illuminate\Database\Migrations\Migration;

class CreateTrackCaterogyFeedsTable extends Migration {

	/**
     * Wondering why there are two create track_category_feeds table
     * The other one is for track_category_consume table.
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('track_category_feeds', function($table)
    {
        $table->engine = 'InnoDB';

        $table->string('category_name', 100);
        $table->integer('total_pins_count');
        $table->integer('new_pins_count');
        $table->integer('timestamp');
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
            Schema::drop('track_category_feeds');
	}

}
