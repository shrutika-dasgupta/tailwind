<?php

class GoogleAnalytics {

    private $token;
    public $profile;

    function __construct($token) {
        $this->token = $token;
        $this->profile = null;
    }

    function call($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $curlheader[0] = sprintf("Authorization: AuthSub token=\"%s\"/n", $this->token);
        $curlheader[1] = "GData-Version: 2\n";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlheader);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    function setProfile($profile) {
        $this->profile = $profile;
    }

    function isValidToken($output)
    {
        if (preg_match("/Token=(.*)/", $output, $matches)) {
            return true;
        } else {
            return false;
        }
    }
    function getToken($output)
    {
        if (preg_match("/Token=(.*)/", $output, $matches)) {
            $sessiontoken = $matches[1];

            return $sessiontoken;
        }

        return "";
    }

}
?>