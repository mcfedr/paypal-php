<?php

namespace Mcfedr\Paypal\Exceptions;

/**
 * The notification currency doesnt match the expected currency
 */
class NotificationCurrencyInvalidException extends NotificationInvalidException
{

    /**
     *
     * @param \Mcfedr\Paypal\Notifications\Notification $notification
     * @param string $has notification currency
     * @param string $expected expected currency
     */
    public function __construct($notification, $has, $expected)
    {
        $this->notification = $notification;
        parent::__construct("Invalid Currency, expected $expected but has $has");
    }

}
