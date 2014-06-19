<?php

use Pinleague\Pinterest;
use Pinleague\PinterestException;

/**
 * Collection of MapProfilesCategoryHash model
 *
 * @author Yesh
 */

class MapProfilesCategoryHash extends DBCollection
{
    public $table = 'map_profiles_category_hash';

    public $columns = [
        'user_id',
        'category',
        'footprint_count',
        'activity_count',
        'influence_count',
        'board_count',
        'recency_order'
        ],
        $primary_key = ['user_id'];

    /**
     * @param $limit
     *
     * @return array
     *
     * @author Yesh
     */
    public static function fetch($limit = 100){

        $DBH = DatabaseInstance::DBO();

        $STH = $DBH->query("SELECT *
                            FROM map_profiles_category_footprint
                            WHERE last_pulled_hash = 0
                            ORDER BY user_id
                            LIMIT $limit");

        return $STH->fetchAll();
    }


    /**
     * The method returns an aggregate count for a given column_name broken down
     * for each category for given array of user_ids
     *
     * @param $column_name The column you want to carry the footprint computations on
     * @param $value It can either be an user_id or domain
     * @param $type It can either be profile or domain
     *
     * @return array
     *
     * TODO: Add domain logic when writing domain engine. Add @var $table_name
     *
     * @author Yesh
     */
    public static function computeFootprint($column_name,
                                            $value,
                                            $type)
    {
        if ($type == "profile") {

            $sql = "SELECT a.category, sum(a.$column_name) sum
                             FROM (
                                 SELECT `influencer_user_id`
                                 FROM cache_profile_influencers
                                 WHERE user_id = $value
                                 ) b
                             LEFT JOIN `map_profiles_category_hash` a
                             ON a.user_id = b.influencer_user_id
                             GROUP BY a.category
                             ORDER BY sum desc";

        } else if ($type == "domain") {

            $sql = "SELECT a.category, sum(a.$column_name) sum, b.period period
                    FROM (
                        SELECT `influencer_user_id`, period
                        FROM `cache_domain_influencers`
                        WHERE domain = '$value') b
                    LEFT JOIN `map_profiles_category_hash` a
                    ON a.user_id = b.influencer_user_id
                    GROUP BY a.category,b.period
                    ORDER BY period,sum desc";
        }

        $DBH = DatabaseInstance::DBO();

        $STH = $DBH->query($sql);

        return $STH->fetchAll();
    }
}

class MapProfilesCategoryHashException extends DBModelException {}
