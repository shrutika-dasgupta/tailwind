<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCacheDomainInfluencersFootprintTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('cache_domain_influencers_footprint', function($table)
        {
            $table->engine = 'InnoDB';

            $table->string('domain', 100);
            $table->string('category', 100);
            $table->integer('period');
            $table->integer('footprint_count');

            $table->primary(['domain', 'category', 'period'], 'domainindex');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('cache_domain_influencers_footprint');
	}

}
