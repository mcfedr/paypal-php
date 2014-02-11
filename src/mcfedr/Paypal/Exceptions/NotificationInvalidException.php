<?php

namespace mcfedr\Paypal\Exceptions;

/**
 * Base of invalid notifications
 */
abstract class NotificationInvalidException extends Exception {

    /**
     * The notification that caused the exception
     * 
     * @var \mcfedr\Paypal\Notifications\Notification
     */
    protected $notification;

    /**
     * The notification that caused the exception
     * 
     * @return \mcfedr\Paypal\Notifications\Notification
     */
    public function getNotification() {
        return $this->notification;
    }

}
