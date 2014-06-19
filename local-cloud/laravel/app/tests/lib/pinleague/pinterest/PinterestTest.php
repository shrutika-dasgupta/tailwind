<?php

use Pinleague\Pinterest;

/**
 * Class PinterestTest
 * Testing suite for the Tailwind Pinterest Class
 *
 * @author  Will
 */
class PinterestTest extends TestCase
{

    /**
     * Test to see if we are getting back an integer
     *
     * @see             \Pinleague\Pinterest::creationDateToTimeStamp
     *
     * @author          Will
     *
     * @timestamp       string Creation date from Pinterest that we turn into epoch time
     */
    public function testCreationDateToTimeStampReturnsEpochTime()
    {
        $timestamp = 'Tue, 05 Mar 2013 13:08:57 +0000';
        $expected_result = 1362488937;

        $result = Pinterest::creationDateToTimeStamp($timestamp);

        $this->assertEquals($expected_result, $result);

    }
}