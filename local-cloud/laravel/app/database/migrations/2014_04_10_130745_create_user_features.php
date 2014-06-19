<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserFeatures extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_features', function(Blueprint $table){

            $table->integer('cust_id');
            $table->integer('feature_id');
            $table->string('value',50);
            $table->integer('added_at');
            $table->integer('updated_at');

            $table->primary(['cust_id','feature_id']);

        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_features');
    }

}
