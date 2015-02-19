<?php

namespace Mcfedr\Paypal\Exceptions;

/**
 * Base of invalid notifications
 */
abstract class NotificationInvalidException extends Exception
{

    /**
     * The notification that caused the exception
     *
     * @var \Mcfedr\Paypal\Notifications\Notification
     */
    protected $notification;

    /**
     * The notification that caused the exception
     *
     * @return \Mcfedr\Paypal\Notifications\Notification
     */
    public function getNotification()
    {
        return $this->notification;
    }

}
