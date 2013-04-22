<?php
namespace Paypal\Products;

/**
 * Describes a recuring product
 */
class Subscription extends Product {
	
	const DAYS = 'D';
	const WEEKS = 'W';
	const MONTHS = 'M';
	const YEARS = 'Y';
	
	/**
	 * The length of the subscription
	 * @var int
	 */
	public $duration;
	
	/**
	 * Unit used for {@link $duration}
	 * @see DAYS
	 * @see WEEKS
	 * @see MONTHS
	 * @see YEARS
	 * @var string
	 */
	public $units;
	
	/**
	 * Trial price of the product
	 * @var double
	 */
	public $trialAmount;
	
	/**
	 * The length of the trial subscription
	 * @var int
	 */
	public $trialDuration;
	
	/**
	 * Unit used for {@link $trialDuration}
	 * @see DAYS
	 * @see WEEKS
	 * @see MONTHS
	 * @see YEARS
	 * @var string
	 */
	public $trialUnits;
	
	/**
	 * Is this a recuring subscription
	 * @var bool
	 */
	public $recuring = true;
	
	/**
	 * Number of times to recur
	 * @var int
	 */
	public $recurLimit;
	
	/**
	 * Whether to reattempt collection of payment when it fails
	 * @var bool
	 */
	public $reattempt = true;
	
	/**
	 * Can the user signup for a new subscription with this button
	 * @var bool 
	 */
	public $allowNew = true;
	
	/**
	 * Can the user modify their existing subscription with this button
	 * @var type 
	 */
	public $allowModify = false;
	
	/**
	 * Let paypal generate a username and password
	 * @var bool 
	 */
	public $generateUsernameAndPassword = false;
	
	/**
	 * The paypal id for this subscription
	 * Set for notification
	 * @var string
	 */
	public $subscriptionId;
	
	/**
	 * The generated username
	 * @var string
	 */
	public $username;
	
	/**
	 * The generated password
	 * @var string
	 */
	public $password;
	
	/**
	 * Get a product from $vars
	 * 
	 * @param array $vars
	 * @param Notification $info
	 */
	public function __construct($vars = null) {
		parent::__construct($vars);
		
		if(!is_null($vars)) {
			if(isset($vars["subscr_id"])) {
				$this->subscriptionId = $vars["subscr_id"];
			}

			if(isset($vars["mc_gross"])) {
				$this->amount = $vars["mc_gross"];
			}
			else if(isset($vars['mc_amount3'])) {
				$this->amount = $vars["mc_amount3"];
			}
			if(isset($vars['period3'])) {
				$p = explode(' ', $vars['period3']);
				$this->duration = $p[0];
				$this->units = $p[1];
			}

			if(isset($vars['mc_amount1'])) {
				$this->trialAmount = $vars["mc_amount1"];
			}
			if(isset($vars['period1'])) {
				$p = explode(' ', $vars['period1']);
				$this->trialDuration = $p[0];
				$this->trialUnits = $p[1];
			}

			if(isset($vars['reattempt'])) {
				$this->reattempt = true;
			}
			else {
				$this->reattempt = false;
			}
			if(isset($vars['recur_times'])) {
				$this->recurLimit = $vars['recur_times'];
			}
			if(isset($vars['recurring'])) {
				$this->recuring = true;
			}
			else {
				$this->recuring = false;
			}

			if(isset($vars['username']) && isset($vars['password'])) {
				$this->username = $vars['username'];
				$this->password = $vars['password'];
				$this->generateUsernameAndPassword = true;
			}
		}
	}
	
	/**
	 * Sets up the array with paypal vars for $product
	 * 
	 * @param array $params
	 */
	public function setParams(&$params, $suffix = '') {
		parent::setParams($params);
		
		$params['a3'] = $this->amount;
		$params['p3'] = $this->duration;
		$params['t3'] = $this->units;
		
		if(!empty($this->trialAmount)) {
			$params['a1'] = $this->trialAmount;
			if(!empty($this->trialDuration)) {
				$params['p1'] = $this->trialDuration;
			}
			else {
				$params['p1'] = $this->duration;
			}
			if(!empty($this->trialUnits)) {
				$params['t1'] = $this->trialUnits;
			}
			else {
				$params['t1'] = $this->units;
			}
		}
		
		if($this->recuring) {
			$params['src'] = 1;
			if(!empty($this->recurLimit)) {
				$params['srt'] = $this->recurLimit;
			}
		}
		
		if($this->reattempt) {
			$params['sra'] = 1;
		}
		
		if($this->allowNew && $this->allowModify) {
			$params['modify'] = 1;			
		}
		else if($this->allowModify) {
			$params['modify'] = 2;
		}
		
		if($this->generateUsernameAndPassword) {
			$params['usr_manage'] = 1;
		}
	}
}
