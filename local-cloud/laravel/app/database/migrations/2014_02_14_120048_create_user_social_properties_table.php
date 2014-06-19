<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserSocialPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_social_properties', function(Blueprint $table)
        {
            $table->bigInteger('cust_id');
            $table->string('type', 50);
            $table->string('name', 50);
            $table->string('value');
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->index(array(
                'cust_id',
            ));

            $table->primary(array(
                'cust_id',
                'type',
                'name',
            ));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_social_properties');
    }

}
