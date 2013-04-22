<?php
namespace Paypal\Exceptions;

abstract class NotificationInvalidException extends Exception {
	
	protected $notification;
	
	public function getNotification() {
		return $this->notification;
	}
}
