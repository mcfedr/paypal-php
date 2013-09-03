<?php

namespace Paypal\Exceptions;

class NotificationCurrencyInvalidException extends NotificationInvalidException {

    public function __construct($notification, $has, $expected) {
        $this->notification = $notification;
        parent::__construct("Invalid Currency, expected $expected but has $has");
    }

}
