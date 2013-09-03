<?php

namespace Paypal\Notifications;

class SubscriptionNotification extends PaymentNotification {

    /**
     * The product purchased in this transaction
     * Can be used to check the right amounts where paid and the cart is what you expected
     * 
     * @var \Paypal\Products\Subscription
     */
    public $product;

    public function __construct($vars) {
        parent::__construct($vars);
        $this->type = static::SUBSCRIPTION;

        if (isset($vars['mc_gross'])) {
            $this->total = $vars['mc_gross'];
            $has = true;
        }
        else if (isset($vars['mc_amount3'])) {
            $this->total = $vars['mc_amount3'];
            $has = true;
        }
        else {
            $has = false;
        }
        
        if (isset($vars['subscr_date'])) {
            $this->date = new \DateTime($vars['subscr_date']);
        }

        if ($has) {
            $this->amount = $this->total;
            $this->product = new \Paypal\Products\Subscription($vars);
        }
    }

}
