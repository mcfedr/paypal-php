<?php

namespace Paypal;

/**
 * Settings used in paypal transactions
 */
class Settings {

	/**
	 * Currency, default is GBP, might be USD or something else
	 * @var string
	 */
	public $currency = 'GBP';

	/**
	 * Local to use on paypal pages, default is GB
	 * @var string 
	 */
	public $local = 'GB';

	/**
	 * Text for cancel button show on paypal pages
	 * @var string
	 */
	public $cancelBtn = 'Return to merchant';

	/**
	 * Can the user change the quantity of items in cart
	 * @var bool
	 */
	public $canChooseQuantity = false;

	/**
	 * Whether to collect shipping info
	 * @var bool
	 */
	public $shippingInfoNeeded = false;

	/**
	 * Allow user to leave a note
	 * @var bool
	 */
	public $allowMerchantNote = false;

	/**
	 * URL of logo image for paypal pages
	 * @var string
	 */
	public $imageURL = null;

	/**
	 * Color of paypal area on paypal pages
	 * eg 'ff0000' for red
	 * @var string
	 */
	public $payflowColor = null;

	/**
	 * Backgroundcolor of header area
	 * eg 'ff0000' for red
	 * @var string
	 */
	public $headerBackgroundColor = null;

	/**
	 * Color of 2px border around the header
	 * eg 'ff0000' for red
	 * @var string
	 */
	public $headerBorderColor = null;

	/**
	 * If you use weight based shipping costyou should set the unit of item weights
	 * @var string
	 */
	public $weightUnit = null;

	/**
	 * Cause all notifations to be logged to stderr
	 * @var bool|\MonoLog\Logger
	 */
	public $logNotifications = false;

	/**
	 * Create a settings object
	 *
	 * @param string $currency
	 * @param bool|\MonoLog\Logger
	 */
	public function __construct($currency = 'GBP', $logging = false) {
		$this->currency = $currency;
		$this->logNotifications = $logging;
	}

	/**
	 * Sets up the array with the vars for the settings
	 * 
	 * @param array $params 
	 */
	public function setParams(&$params) {
		if (!empty($this->local)) {
			$params['lc'] = $this->local;
		}
		if (!empty($this->currency)) {
			$params['currency_code'] = $this->currency;
		}
		if (!empty($this->cancelBtn)) {
			$params['cbt'] = $this->cancelBtn;
		}
		$params['undefined_quantity'] = !empty($this->canChooseQuantity) && $this->canChooseQuantity ? 1 : 0;
		$params['no_shipping'] = !empty($this->shippingInfoNeeded) && $this->shippingInfoNeeded ? 0 : 1;
		$params['no_note'] = !empty($this->allowMerchantNote) && $this->allowMerchantNote ? 0 : 1;
		$params["weight_unit"] = empty($this->weight_unit) ? 'kgs' : $this->weight_unit;
		if (!empty($this->imageURL)) {
			$params['image_url'] = $this->imageURL;
		}
		if (!empty($this->payflowColor)) {
			$params['cpp_payflow_color'] = $this->payflowColor;
		}
		if (!empty($this->headerBackgroundColor)) {
			$params['cpp_headerback_color'] = $this->headerBackgroundColor;
		}
		if (!empty($this->headerBorderColor)) {
			$params['cpp_headerborder_color'] = $this->headerBorderColor;
		}
	}

}
