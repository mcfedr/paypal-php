<?php
namespace Paypal\Notifications;

abstract class PaymentNotification extends Notification {
	
	/**
	 * Info about buyer
	 * @var Paypal\Buyer
	 */
	public $buyer;
	
	/**
	 * Note left by buyer (if you allowed him to leave it)
	 * @var string
	 */
	public $note;
	
	/**
	 * If $status == 'Pending' this is why
	 * @var string
	 */
	public $pendingReason;
	
	public function __construct($vars) {
		parent::__construct($vars);
		
		$this->buyer = new \Paypal\Buyer($vars);
		
		if(isset($vars['mc_currency'])) {
			$this->currency = $vars['mc_currency'];
		}
		
		if(isset($vars['mc_fee'])) {
			$this->fee = $vars['mc_fee'];
		}
		
		if(isset($vars['business'])) {
			$this->business = $vars['business'];
		}
		
		if(isset($vars['memo'])) {
			$this->note = $vars['memo'];
		}
	}
}
