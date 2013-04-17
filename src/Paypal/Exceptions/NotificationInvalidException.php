<?php
namespace Paypal\Exceptions;

class NotificationInvalidException extends Exception {
	
	private $notification;
	
	public function __construct($notification) {
		$this->notification = $notification;
		parent::__construct("Local verification failed");
	}
	
	public function getNotification() {
		return $this->notification;
	}
}
