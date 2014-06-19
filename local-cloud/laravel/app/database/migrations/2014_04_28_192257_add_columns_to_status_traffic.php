<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToStatusTraffic extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
    public function up()
    {
        Schema::table('status_traffic', function(Blueprint $table)
        {
            $table->string('timezone',50)->after('token');
            $table->string('currency', 20)->after('token');
            $table->tinyInteger('eCommerceTracking')->after('token');
            $table->string('websiteUrl',100)->after('token');
            $table->string('track_type', 30)->after('last_calced');
            $table->integer('added_at')->after('last_calced');
            $table->dropPrimary('PRIMARY');
            $table->primary(array('account_id', 'profile', 'token', 'track_type'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('status_traffic', function(Blueprint $table)
        {
            $table->dropColumn('timezone');
            $table->dropColumn('currency');
            $table->dropColumn('eCommerceTracking');
            $table->dropColumn('websiteUrl');
            $table->dropColumn('track_type');
            $table->dropColumn('added_at');
            $table->dropPrimary('PRIMARY');
            $table->primary(array('account_id','profile'));
        });
    }

}
