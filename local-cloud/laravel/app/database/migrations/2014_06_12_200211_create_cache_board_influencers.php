<?php

use Illuminate\Database\Migrations\Migration;

class CreateCacheBoardInfluencers extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
    public function up()
    {
        Schema::create('cache_board_influencers', function($table){

            $table->bigInteger('board_id');
            $table->bigInteger('user_id');
            $table->bigInteger('influencer_user_id');
            $table->string('influencer_username', 25);
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->integer('follower_count');
            $table->integer('following_count');
            $table->string('image', 200);
            $table->string('website', 100);
            $table->string('facebook', 100);
            $table->string('twitter', 50);
            $table->string('location', 100);
            $table->integer('board_count');
            $table->integer('pin_count');
            $table->integer('like_count');
            $table->integer('created_at');
            $table->integer('timestamp');

            $table->primary(array('board_id', 'user_id', 'influencer_user_id'));
//            $table->index(array('user_id', 'follower_count'));
        });
    }

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('cache_board_influencers');
	}

}

/**
 *
 *
 * 
CREATE TABLE `cache_board_influencers` (
`board_id` bigint(20) NOT NULL,
`user_id` bigint(20) NOT NULL,
`influencer_user_id` bigint(20) NOT NULL DEFAULT '0',
`influencer_username` varchar(25) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
`first_name` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
`last_name` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
`follower_count` int(11) DEFAULT NULL,
`following_count` int(11) DEFAULT NULL,
`image` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
`website` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
`facebook` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
`twitter` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
`location` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
`board_count` int(11) DEFAULT NULL,
`pin_count` int(11) DEFAULT NULL,
`like_count` int(11) DEFAULT NULL,
`created_at` int(11) DEFAULT NULL,
`timestamp` int(11) DEFAULT NULL,
PRIMARY KEY (`board_id`,`user_id`,`influencer_user_id`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=TOKUDB_LZMA;

create clustering index user_id_follower_count_cidx on cache_board_influencers (user_id, follower_count);
 *
 *
 */

