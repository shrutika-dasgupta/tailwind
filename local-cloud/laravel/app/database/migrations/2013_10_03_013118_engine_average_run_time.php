<?php

use Illuminate\Database\Migrations\Migration;

class EngineAverageRunTime extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('status_engines', function ($table) {
            $table->dropColumn('average_run_time');
            $table->dropColumn('runs');
        });
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('status_engines', function ($table) {
            $table->integer('average_run_time');
            $table->integer('runs');
        });
    }

}