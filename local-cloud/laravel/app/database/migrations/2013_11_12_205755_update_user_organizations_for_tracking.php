<?php

use Illuminate\Database\Migrations\Migration;

class UpdateUserOrganizationsForTracking extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('user_organizations', function ($table) {
            $table->string('coupon_code',50)->nullable()->default(NULL);
            $table->string('subscription_state',50);
            $table->integer('component_count')->default(0);
            $table->integer('trial_start_at')->default(0);
            $table->integer('trial_end_at')->default(0);
            $table->integer('billing_event_count')->default(0);
            $table->integer('first_billing_event_at')->default(0);
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('user_organizations', function ($table) {
            $table->dropColumn('coupon_code');
            $table->dropColumn('subscription_state');
            $table->dropColumn('component_count');
            $table->dropColumn('trial_start_at');
            $table->dropColumn('trial_end_at');
            $table->dropColumn('billing_event_count');
            $table->dropColumn('first_billing_event_at');
        });
	}

}