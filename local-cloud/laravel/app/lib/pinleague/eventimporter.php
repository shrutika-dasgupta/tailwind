<?php
namespace Pinleague;

class EventImporter
{
    public $api_key;
    public $host = 'http://api.mixpanel.com/';
    public $token;

    public function __construct($token_string, $api_key)
    {
        $this->token   = $token_string;
        $this->api_key = $api_key;
    }

    function track($event, $properties = array())
    {
        $params = array(
            'event'      => $event,
            'properties' => $properties
        );

        if (!isset($params['properties']['token'])) {
            $params['properties']['token'] = $this->token;
        }

        $url = $this->host . 'import/?data=' . base64_encode(json_encode($params)) . "&api_key=$this->api_key";
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        $headers=array(
            "content-length: 0"
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        $result = curl_exec($ch);

        curl_close($ch);

        return true;
    }
}
