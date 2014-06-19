<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEstimateToCalcsProfileHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('calcs_profile_history', function(Blueprint $table)
        {
            $table->tinyInteger('estimate')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('calcs_profile_history', function(Blueprint $table)
        {
            $table->dropColumn('estimate');
        });
    }
}