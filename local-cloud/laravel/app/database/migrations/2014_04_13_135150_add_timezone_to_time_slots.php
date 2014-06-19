<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTimezoneToTimeSlots extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('publisher_time_slots', function(Blueprint $table)
        {
            $table->string('timezone',30)->after('time_preference');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('publisher_time_slots', function(Blueprint $table)
        {
            $table->dropColumn('timezone');
        });
    }

}
