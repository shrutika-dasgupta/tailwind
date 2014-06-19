<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserOrganizationsColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('user_organizations', function(Blueprint $table)
		{

            $table->integer('trial_stopped_at')
                ->nullable()
                ->default(NULL)
                ->after('trial_end_at');

            $table->integer('trial_converted_at')
                  ->nullable()
                  ->default(NULL)
                  ->after('trial_end_at');

            $table->integer('last_billing_event_at')
                  ->nullable()
                  ->default(NULL)
                  ->after('first_billing_event_at');

            $table->decimal('last_billing_amount',10,2)
                  ->default(0)
                  ->after('first_billing_event_at');

            $table->decimal('total_amount_billed',10,2)
                  ->default(0)
                  ->after('first_billing_event_at');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('user_organizations', function(Blueprint $table)
		{
			//
            $table->dropColumn('trial_stopped_at');
            $table->dropColumn('trial_converted_at');
            $table->dropColumn('last_billing_event_at');
            $table->dropColumn('last_billing_amount');
            $table->dropColumn('total_amount_billed');
		});
	}

}