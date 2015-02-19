<?php

namespace Mcfedr\Paypal\Exceptions;

/**
 * Paypal indicates a problem with an api call
 */
abstract class ACKException extends Exception {

    private $response;

    /**
     *
     * @param array $response
     */
    public function __construct($response) {
        $this->response = $response;
        parent::__construct("{$response['ACK']} {$response['L_SHORTMESSAGE0']} {$response['L_LONGMESSAGE0']}", $response['ACK']);
    }

    /**
     * The complete response
     *
     * @return array
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * Long message from paypal
     *
     * @return string
     */
    public function getLongMessage() {
        return $this->response['L_LONGMESSAGE0'];
    }

    /**
     * Short message from paypal
     *
     * @return string
     */
    public function getShortMessage() {
        return $this->response['L_SHORTMESSAGE0'];
    }

}
