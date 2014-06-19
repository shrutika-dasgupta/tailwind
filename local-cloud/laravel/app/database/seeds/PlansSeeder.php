<?php

/**
 * Class FeaturesSeeder
 * Creates the defaults for features table
 */
class PlansSeeder extends Seeder
{

    /**
     * Seeds the features table with defaults
     */
    public function run()
    {

        $defaults = array(
            [1,'Free', 2974497],
            [2,'Lite', 3319111],
            [3,'Pro', 3319112],
            [4,'Agency', 3319114],
            [5,'Legacy-Lite', 3319111],
            [6,'Legacy-Pro', 3319112]

        );

        DB::table('user_plans')->truncate();

        foreach ($defaults as list($plan_id, $plan_name,$chargify_id)) {

            DB::insert('INSERT INTO user_plans (`id`,`name`,`chargify_plan_id`) VALUES (?,?,?)',
                       [$plan_id, $plan_name, $chargify_id]);

        }

    }

}