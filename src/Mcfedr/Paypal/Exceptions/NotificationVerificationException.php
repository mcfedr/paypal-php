<?php

namespace Mcfedr\Paypal\Exceptions;

class NotificationVerificationException extends NotificationInvalidException {

    private $response;

    /**
     *
     * @param string $response
     * @param \Mcfedr\Paypal\Notifications\Notification $notification
     */
    public function __construct($response, $notification) {
        $this->response = $response;
        $this->notification = $notification;
        parent::__construct("Verification failed");
    }

    public function getResponse() {
        return $this->response;
    }

}
