<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExpiresAtToUserAccount extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_accounts', function (Blueprint $table) {

            $table->integer('expires_at')->after('access_token');
            $table->string('token_authorized',30)->after('access_token');
            $table->string('token_scope')->after('access_token');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_accounts', function (Blueprint $table) {
            $table->dropColumn('expires_at');
            $table->dropColumn('token_authorized');
            $table->dropColumn('token_scope');
        });
    }

}
