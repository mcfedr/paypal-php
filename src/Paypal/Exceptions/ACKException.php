<?php
namespace Paypal\Exceptions;

abstract class ACKException extends Exception {
	private $response;
	
	public function __construct($response) {
		$this->response = $response;
		parent::__construct("{$response['ACK']} {$response['L_SHORTMESSAGE0']} {$response['L_LONGMESSAGE0']}", $response['ACK']);
	}
	
	public function getResonse() {
		return $this->response;
	}
	
	public function getLongMessage() {
		return $this->response['L_LONGMESSAGE0'];
	}
	
	public function getShortMessage() {
		return $this->response['L_SHORTMESSAGE0'];
	}
}
