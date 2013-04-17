<?php
namespace Paypal\Exceptions;

class NotificationVerifiationException extends Exception {
	
	private $response;
	
	public function __construct($response) {
		$this->response = $response;
		parent::__construct("Verification failed");
	}
	
	public function getResonse() {
		return $this->response;
	}
}
