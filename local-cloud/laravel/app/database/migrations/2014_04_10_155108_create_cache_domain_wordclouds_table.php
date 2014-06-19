<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCacheDomainWordcloudsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
    public function up()
    {
        Schema::create('cache_domain_wordclouds', function(Blueprint $table){

            $table->string('domain', 100);
            $table->integer('date');
            $table->string('word', 50);
            $table->integer('word_count');
            $table->integer('timestamp');

            $table->primary(array('domain', 'date', 'word'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cache_domain_wordclouds');
    }

}