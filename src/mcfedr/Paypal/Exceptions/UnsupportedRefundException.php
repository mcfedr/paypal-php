<?php
/**
 * Created by PhpStorm.
 * User: mcfedr
 * Date: 11/02/2014
 * Time: 15:19
 */

namespace mcfedr\Paypal\Exceptions;


class UnsupportedRefundException extends Exception {
    public function __construct($type) {
        parent::__construct("Cannot do a refund other than 'Full', attempted $type");
    }
} 