<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMapTopicRelationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('map_topic_relations', function(Blueprint $table)
        {
            $table->integer('topic_id');
            $table->string('related_topic');

            $table->primary(array('topic_id',
                                  'related_topic'));

        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('map_topic_relations');
	}

}
