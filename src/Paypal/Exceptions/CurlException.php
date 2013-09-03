<?php

namespace Paypal\Exceptions;

class CurlException extends Exception {

    private $url;
    private $data;
    private $curlMessage;

    public function __construct($ch, $url, $data) {
        $this->url = $url;
        $this->data = $data;
        $this->curlMessage = curl_error($ch);
        parent::__construct("Error posting to paypal, " . curl_error($ch), curl_errno($ch));
    }

    public function getUrl() {
        return $this->url;
    }

    public function getData() {
        return $this->data;
    }

    public function getCurlMessage() {
        return $this->curlMessage;
    }

}
