<?php

namespace Paypal\Exceptions;

class NotificationInvalidException extends Exception {

    protected $notification;

    public function getNotification() {
        return $this->notification;
    }

}
