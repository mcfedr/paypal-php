<?php

namespace Paypal\Notifications;

class CartChangeNotification extends CartNotification {

    public function __construct($vars) {
        parent::__construct($vars);
        $this->type = static::REFUND;
    }

}
