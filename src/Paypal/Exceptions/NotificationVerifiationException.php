<?php

namespace Paypal\Exceptions;

class NotificationVerifiationException extends NotificationInvalidException {

    private $response;

    /**
     * 
     * @param string $response
     * @param \Paypal\Notifications\Notification $notication
     */
    public function __construct($response, $notification) {
        $this->response = $response;
        $this->notification = $notification;
        parent::__construct("Verification failed");
    }

    public function getResonse() {
        return $this->response;
    }

}
