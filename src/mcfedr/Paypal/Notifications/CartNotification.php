<?php

namespace mcfedr\Paypal\Notifications;

use mcfedr\Paypal\Products\CartProduct;

class CartNotification extends PaymentNotification {

    /**
     * Amount paid for shipping
     * @var double
     */
    public $shipping;

    /**
     * Amount paid for handling
     * @var double
     */
    public $handling;

    /**
     * The products purchased in this transaction
     * Can be used to check the right amounts where paid and the cart is what you expected
     * @var array {@link mcfedr\Paypal\Products\CartProduct}
     */
    public $products;

    public function __construct($vars) {
        parent::__construct($vars);
        $this->type = static::CART;

        if (isset($vars['mc_handling'])) {
            $this->handling = $vars['mc_handling'];
        }

        if (isset($vars['mc_shipping'])) {
            $this->shipping = $vars['mc_shipping'];
        }
        else if (isset($vars['shipping'])) {
            $this->shipping = $vars['shipping'];
        }

        if (isset($vars['mc_gross'])) {
            $this->total = $vars['mc_gross'];
            $this->amount = $this->total - (isset($this->handling) ? $this->handling : 0) - (isset($this->shipping) ? $this->shipping : 0);
        }

        $this->products = array();
        for ($i = 1; isset($vars["item_name$i"]); $i++) {
            $this->products[] = new CartProduct($vars, $i);
        }
        if (isset($vars['item_name'])) {
            $this->products[] = new CartProduct($vars);
        }
        return $this;
    }

}
