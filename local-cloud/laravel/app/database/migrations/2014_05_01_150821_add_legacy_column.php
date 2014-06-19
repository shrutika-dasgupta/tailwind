<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLegacyColumn extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_organizations', function(Blueprint $table)
        {
            $table->tinyInteger('is_legacy')
                ->after('plan_level')
                ->default('1')
                ->is_nullable();
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
            $table->dropColumn('is_legacy');
        });
    }

}
