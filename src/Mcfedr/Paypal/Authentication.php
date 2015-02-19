<?php

namespace Mcfedr\Paypal;

/**
 * Class with read only vars for using paypal api's
 */
class Authentication {

    private $email;
    private $username;
    private $password;
    private $signature;
    private $sandbox;

    public function __construct($email, $username, $password, $signature, $sandbox = false) {
        $this->email = $email;
        $this->username = $username;
        $this->password = $password;
        $this->signature = $signature;
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

    public function getSignature() {
        return $this->signature;
    }

    public function isSandbox() {
        return $this->sandbox;
    }

}
