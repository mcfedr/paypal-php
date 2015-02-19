<?php

namespace Mcfedr\Paypal\Exceptions;

/**
 * The person being paid is not the expected one
 */
class NotificationBusinessInvalidException extends NotificationInvalidException {

    /**
     *
     * @param \Mcfedr\Paypal\Notifications\Notification $notification
     */
    public function __construct($notification) {
        $this->notification = $notification;
        parent::__construct("Invalid Business");
    }

}
