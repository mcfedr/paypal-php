<?php

namespace Mcfedr\Paypal\Exceptions;


class UnsupportedRefundException extends Exception {
    public function __construct($type) {
        parent::__construct("Cannot do a refund other than 'Full', attempted $type");
    }
}
