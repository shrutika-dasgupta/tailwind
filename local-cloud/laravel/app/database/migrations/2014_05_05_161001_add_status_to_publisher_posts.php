<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatusToPublisherPosts extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('publisher_posts', function(Blueprint $table)
        {
            $table->string('status',1)->default('Q');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('publisher_posts', function(Blueprint $table)
        {
            $table->dropColumn('status');
        });
    }

}
