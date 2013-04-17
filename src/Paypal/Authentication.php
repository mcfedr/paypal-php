<?php
namespace Paypal;
/**
* Class with read only vars for using paypal api's
*/
class Authentication {
	private $email;
	private $username;
	private $password;
	private $signiture;
	private $sandbox;
	
	public function __construct($email, $username, $password, $signiture, $sandbox = false) {
		$this->email = $email;
		$this->username = $username;
		$this->password = $password;
		$this->signiture = $signiture;
		$this->sandbox = $sandbox;
	}
	
	public function getEmail() {
		return $this->email;
	}
	
	public function getUsername() {
		return $this->username;
	}
	
	public function getPassword() {
		return $this->password;
	}
	
	public function getSigniture() {
		return $this->signiture;
	}
	
	public function isSandbox() {
		return $this->sandbox;
	}
}
