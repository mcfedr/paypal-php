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
		if(isset($vars['mc_gross'])) {
			$this->total = $vars['mc_gross'];
			$this->amount = $this->total;
			$this->product = new \Paypal\Products\Subscription($vars, $this);
		}
	}
}
