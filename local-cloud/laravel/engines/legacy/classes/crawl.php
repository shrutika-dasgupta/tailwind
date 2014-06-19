<?php

class Crawl {

    public $url, $parameters;

    public $content;
    public $curl;


    function __construct($url, $parameters) {
        $this->url = $url;
        $this->parameters = $parameters;
    }

    function setResponse($content) {
        $this->content = $content;
    }

    function setCurl($curl) {
        $this->curl = $curl;
    }
}



?>