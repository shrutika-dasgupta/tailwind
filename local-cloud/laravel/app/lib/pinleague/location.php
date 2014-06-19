<?php

    /*
     * $location = new \Pinleague\Location('brooklyn ny');
     *
     * echo $location->longitude; //  -70.7630556
     * echo $location->latitude; //   41.8833333
     *
     */

    namespace Pinleague;

    /*
     * Wrapper class to get location data
     *
     * @TODO
     * add other api endpoints if we hit rate limits
     * store strings in our DB so we don't keep making calls out
     *
     * @authors Will
     */

    class Location
    {

        public $latitude, $longitude;
        public $street_number, $street_name;
        public $city, $zip, $region, $country;

       protected $mashapeKey;

        public function __construct($location)
        {

            $this->mashapeKey = "OoN7u8drAsTcpPx9aenWvYDKm5UxH00C";

            $response = \Unirest::get(
                "https://montanaflynn-geocode-location-information.p.mashape.com/address?address=$location",
                array(
                    "X-Mashape-Authorization" => $this->mashapeKey
                ));


            $this->latitude      = $response->body->latitude;
            $this->longitude     = $response->body->longitude;
            $this->street_number = $response->body->street_number;
            $this->street_name   = $response->body->street_name;
            $this->city          = $response->body->city;
            $this->zip           = $response->body->zip;
            $this->region        = $response->body->region;
            $this->country       = $response->body->country;

        }

    }
