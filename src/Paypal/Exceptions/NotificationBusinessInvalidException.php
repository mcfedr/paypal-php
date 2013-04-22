<?php
namespace Paypal\Exceptions;

class NotificationBusinessInvalidException extends NotificationInvalidException {
	public function __construct($notification) {
		$this->notification = $notification;
		parent::__construct("Invalid Business");
	}
}
