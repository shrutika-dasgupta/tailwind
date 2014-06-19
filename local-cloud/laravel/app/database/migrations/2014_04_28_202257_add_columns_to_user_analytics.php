<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToUserAnalytics extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
    public function up()
    {
        Schema::table('user_analytics', function(Blueprint $table)
        {
            $table->string('name', 50)->after('token');
            $table->string('timezone',50)->after('token');
            $table->string('currency', 20)->after('token');
            $table->integer('accountId')->after('token');
            $table->string('webPropertyId',30)->after('token');
            $table->tinyInteger('eCommerceTracking')->after('token');
            $table->string('websiteUrl',100)->after('token');
            $table->string('track_type', 20)->after('last_calced');
            $table->integer('added_at')->after('last_calced');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_analytics', function(Blueprint $table)
        {
            $table->dropColumn('name');
            $table->dropColumn('timezone');
            $table->dropColumn('currency');
            $table->dropColumn('accountId');
            $table->dropColumn('webPropertyId');
            $table->dropColumn('eCommerceTracking');
            $table->dropColumn('websiteUrl');
            $table->dropColumn('track_type');
            $table->dropColumn('added_at');
        });
    }
}
