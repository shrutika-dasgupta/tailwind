<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserPlanFeatures extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plan_features', function(Blueprint $table){

            $table->integer('plan_id');
            $table->integer('feature_id');
            $table->string('value',50);
            $table->integer('added_at');
            $table->integer('updated_at');

            $table->primary(['plan_id','feature_id']);

        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('plan_features');
    }

}
