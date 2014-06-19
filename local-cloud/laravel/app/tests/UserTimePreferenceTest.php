<?php

use Pinleague\Pinterest;
use UserTimePreference;

/**
 * Class UserTimePreference
 * Testing suite for the UserTimePreference Model
 *
 * @author Yesh
 */
class UserTimePreferenceTest extends TestCase
{
    public function testSaveTimePreferenceAuto(){
        $preference_details = array("account_id" => 219,
                                    "day_preference" => 1,
                                    "time_preference" => "3:00 PM");

    }


}
