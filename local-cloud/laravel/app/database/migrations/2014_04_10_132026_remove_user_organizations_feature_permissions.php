<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveUserOrganizationsFeaturePermissions extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('user_organizations_feature_permissions', function(Blueprint $table)
        {
            $table->increments('org_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::drop('user_organizations_feature_permissions');
    }

}
