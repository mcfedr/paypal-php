<?php
/**
 * For accessing the paypal api
 * See the test file for examples of usage
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * @author Fred Cox <mcfedr@gmail.com>
 * @copyright Copyright Fred Cox, 2011
 * @package paypal-php
 * @subpackage libpaypal
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 */
class Paypal {
	
	/**
	* Authentication to use
	* @var PaypalAuthentication
	*/
	private $authentication;
	
	/**
	* Settings to use
	* @var PaypalSettings
	*/
	private $settings;
	
	/**
	* last error message
	* @var string
	*/
	private $lastError;
	
	/**
	 * Create a new paypal object
	 * 
	 * @param PaypalAuthentication $authentication 
	 * @param PaypalSettings $settings 
	 */
	public function __construct($authentication, $settings = null) {
		$this->authentication = $authentication;
		if($settings == null) {
			$settings = new PaypalSettings();
		}
		$this->settings = $settings;
	}
	
	/**
	 * Get a button as html
	 * Use getButtonParams and getButtonAction if you need to customise
	 *
	 * @param PaypalProduct|array $products {@link PaypalProduct}
	 * @param string $paidURL URL paypal returns user to when completed
	 * @param string $cancelURL URL to return to if user cancels payment
	 * @param string $notifyURL URL for instant notifications
	 * @param int $invoiceId Your id for this payment, must be unique
	 * @param string $custom Custom data that will be in any notifications
	 * @param PaypalBuyer $buyer Info about the buyer to autofill in
	 * @param string label Label for the button
	 * @return string html form with a button
	 */
	public function getButton($products, $paidURL, $cancelURL, $notifyURL = null, $invoiceId = null, $custom = null, $buyer = null, $label = "Checkout") {
		$action = $this->getButtonAction();
		$params = $this->getButtonParams($products, $paidURL, $cancelURL, $notifyURL, $invoiceId, $custom, $buyer);
		$ret = "<form action=\"$action\" method=\"post\">";
		foreach($params as $key => $value) {
			$ret .= "<input type=\"hidden\" name=\"$key\" value=\"$value\"/>";
		}
		$ret .= "<button type=\"submit\">$label</button>";
		$ret .= "</form>";
		return $ret;
	}
	
	/**
	 * Get paypal button params for this payment
	 * Should be posted to url getButtonAction
	 * use <input type="hidden" name="$key" value="$value" />
	 * for each element of the array returned
	 * 
	 * @param PaypalProduct|array $products {@link PaypalProduct}
	 * @param string $paidURL URL paypal returns user to when completed
	 * @param string $cancelURL URL to return to if user cancels payment
	 * @param string $notifyURL URL for instant notifications
	 * @param int $invoiceId Your id for this payment, must be unique
	 * @param string $custom Custom data that will be in any notifications
	 * @param PaypalBuyer $buyer Info about the buyer to autofill in
	 * @return array of string 
	 */
	public function getButtonParams($products, $paidURL, $cancelURL, $notifyURL = null, $invoiceId = null, $custom = null, $buyer = null) {
		$params = array();
		if(!is_array($products) || count($products) == 1) {
			if(!is_array($products)) {
				$product = $products;
			}
			else {
				$product = $products[0];
			}
			$params['cmd'] = '_xclick';
			
			$this->setProductParams($product, $params);
		}
		else {
			$params['cmd'] = '_cart';
			$params['upload'] = 1;
			$i = 1;
			foreach($products as $product) {
				$this->setProductParams($product, $params, "_$i");
				$i++;
			}
		}
		
		$params['return'] = $paidURL;
		$params['cancel_return'] = $cancelURL;
		
		if(!empty($notifyURL)) {
			$params['notify_url'] = $notifyURL;
		}
		if(!empty($custom)) {
			$params['custom'] = $custom;
		}
		if(!empty($invoiceId)) {
			$params['invoice'] = $invoiceId;
		}
		
		if(!empty($buyer)) {
			$this->setBuyerParams($buyer, $params);
		}
		
		$this->setSettingsParams($this->settings, $params);
		
		$params['business'] = $this->authentication->getEmail();
		return $params;
	}
	
	/**
	 * Set params in button for buyer
	 * @param PaypalBuyer $buyer
	 * @param array $params 
	 */
	private function setBuyerParams($buyer, &$params) {
		if(!empty($buyer->email)) {
			$params['email'] = $buyer->email;
		}
		if(!empty ($buyer->firstName)) {
			$params['first_name'] = $buyer->firstName;
		}
		if(!empty($buyer->lastName)) {
			$params['last_name'] = $buyer->lastName;
		}
		//TODO: address fields
	}


	/**
	* Get form action to go with the params from getButtonParams
	* 
	* @return string
	*/
	public function getButtonAction() {
		if($this->authentication->isSandbox()) {
			return 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		}
		else {
			return 'https://www.paypal.com/cgi-bin/webscr';
		}
	}
	
	/**
	 * Sets up the array with paypal vars for $product
	 * 
	 * @param PaypalProduct $product
	 * @param array $params
	 * @param string $suffix used when more than one product is set eg "_1", "_2"
	 */
	private function setProductParams($product, &$params, $suffix = '') {
		if(!empty($product->id)) {
			$params["item_number$suffix"] = $product->id;
		}
		$params["item_name$suffix"] = substr($product->name, 0, 127);
		$params["amount$suffix"] = $product->amount;
		$params["quantity$suffix"] = empty($product->quantity) ? 1 : $product->quantity;
		if(!empty($product->discount)) {
			$params["discount_amount$suffix"] = $product->discount;
		}
		if(!empty($product->tax)) {
			$params["tax$suffix"] = $product->tax;
		}
		if(!empty($product->shipping)) {
			$params["shipping$suffix"] = $product->shipping;
			if(!empty($product->shipping2)) {
				$params["shipping2$suffix"] = $product->shipping2;
			}
			else {
				$params["shipping2$suffix"] = $product->shipping;
			}
		}
		if(!empty($product->handling)) {
			$params["handling$suffix"] = $product->handling;
		}
		if(!empty($product->weight)) {
			$params["weight$suffix"] = $product->weight;
		}
	}	
	
	/**
	 * Sets up the array with the vars for $settings
	 * 
	 * @param PaypalSettings $settings
	 * @param array $params 
	 */
	private function setSettingsParams($settings, &$params) {
		if(!empty($settings->local)) {
			$params['lc'] = $settings->local;
		}
		if(!empty($settings->currency)) {
			$params['currency_code'] = $settings->currency;
		}
		if(!empty($settings->cancelBtn)) {
			$params['cbt'] = $settings->cancelBtn;
		}
		$params['undefined_quantity'] = !empty($settings->canChooseQuantity) && $settings->canChooseQuantity ? 1 : 0;
		$params['no_shipping'] = !empty($settings->shippingInfoNeeded) && $settings->shippingInfoNeeded ? 0 : 1;
		$params['no_note'] = !empty($settings->allowMerchantNote) && $settings->allowMerchantNote ? 0 : 1;
		$params["weight_unit"] = empty($settings->weight_unit) ? 'kgs' : $settings->weight_unit;
		if(!empty($settings->imageURL)) {
			$params['image_url'] = $settings->imageURL;
		}
		if(!empty($settings->payflowColor)) {
			$params['cpp_payflow_color'] = $settings->payflowColor;
		}
		if(!empty($settings->headerBackgroundColor)) {
			$params['cpp_headerback_color'] = $settings->headerBackgroundColor;
		}
		if(!empty($settings->headerBorderColor)) {
			$params['cpp_headerborder_color'] = $settings->headerBorderColor;
		}
	}
	
	/**
	* Call this function on your instant notification url (IN)
	* And success url to use payment data transfer (PDT)
	 * 
	* @param array $vars variables to use, normally $_POST
	* @return PaypalNotification
	*/
	public function handleNotification($vars = null) {
		if($vars == null) {
			$vars = $_REQUEST;
		}
		$handled = false;
		if(($verified = $this->verifyNotification($vars))) {
			if(isset($vars['txn_type'])) {
				switch($vars['txn_type']) {
					case 'cart':
					case 'web_accept':
						$handled = $this->getNotification($vars);
						$handled->type = PaypalNotification::CART;
						break;
					case 'masspay':
						$handled = $this->getMassNotifications($vars);
						$handled->type = PaypalNotification::MASSPAY;
						break;
				}
			}
			else if(isset($vars['payment_status'])) {
				switch($vars['payment_status']) {
					case 'Refunded':
					case 'Reversed':
					case 'Canceled_Reversal':
						$handled = $this->getNotification($vars);
						$handled->type = PaypalNotification::REFUND;
						break;
				}
			}
			
		}
		if(!$handled->ok || $this->settings->logNotifications) {
			$this->error(($verified ? '' : 'Unverified ') . ($handled->ok ? '' : 'Unhandled ') . 'paypal notification ' . $this->urlPairs($vars));
		}
		return $handled;
	}
	
	private function getMassNotifications($vars) {
		$info = new PaypalNotification();
		
		if(isset($vars['payment_status'])) {
			$info->status = $vars['payment_status'];
		}
		if(isset($vars['pending_reason'])) {
			$info->pendingReason = $vars['pending_reason'];
		}
		
		$info->notifications = array();
		$info->ok = true;
		for($i = 1; isset($vars["status_$i"]); $i++) {
			$n = $this->getMassNotification($vars, $i);
			$info->notifications[] = $n;
			$info->ok = $info->ok && $n->ok;
		}
		if(empty($info->notifications)) {
			$info->ok = false;
		}
		return $info;
	}
	
	private function getMassNotification($vars, $i) {
		$info = new PaypalNotification();
		$info->type = PaypalNotification::MASSPAY;
		
		if(isset($vars['txn_type'])) {
			$info->transactionType = $_POST['txn_type'];
		}
		if(isset($vars["masspay_txn_id_$i"])) {
			$info->transactionId = $vars["masspay_txn_id_$i"];
		}
		if(isset($vars["unique_id_$i"])) {
			$info->invoiceId = $vars["unique_id_$i"];
		}
		if(isset($vars["mc_gross_$i"])) {
			$info->total = $vars["mc_gross_$i"];
			$info->amount = $info->total;
		}
		if(isset($vars["mc_fee_$i"])) {
			$info->fee = $vars["mc_fee_$i"];
		}
		if(isset($vars["status_$i"])) {
			$info->status = $vars["status_$i"];
		}
		if(isset($vars["mc_currency_$i"])) {
			$info->currency = $vars["mc_currency_$i"];
			$info->currencyCorrect = $info->currency == $this->settings->currency;
		}
		if(isset($vars['payer_email'])) {
			$info->business = $vars['payer_email'];
			$sandbox = isset($vars['test_ipn']) && $vars['test_ipn'] == 1;
			$info->businessCorrect = $info->business == $this->authentication->getEmail() && $sandbox == $this->authentication->isSandbox();
		}
		if(isset($vars['payment_date'])) {
			$info->date = strtotime($vars['payment_date']);
		}
		$info->ok = $info->businessCorrect && $info->currencyCorrect;
		return $info;
	}
	
	/**
	 * Creates a PaypalNotification object from $vars
	 * @param array $vars
	 * @return PaypalNotification 
	 */
	private function getNotification($vars) {
		$info = new PaypalNotification();
		
		if(isset($vars['txn_type'])) {
			$info->transactionType = $_POST['txn_type'];
		}
		if(isset($vars['txn_id'])) {
			$info->transactionId = $vars['txn_id'];
		}
		if(isset($vars['parent_txn_id'])) {
			$info->parentTransactionId = $vars['parent_txn_id'];
		}
		if(isset($vars['invoice'])) {
			$info->invoiceId = $vars['invoice'];
		}
		if(isset($vars['custom'])) {
			$info->custom = $vars['custom'];
		}
		if(isset($vars['payment_status'])) {
			$info->status = $vars['payment_status'];
		}
		if(isset($vars['pending_reason'])) {
			$info->pendingReason = $vars['pending_reason'];
		}
		if(isset($vars['mc_handling'])) {
			$info->handling = $vars['mc_handling'];
		}
		if(isset($vars['mc_shipping'])) {
			$info->shipping = $vars['mc_shipping'];
		}
		else if(isset($vars['shipping'])) {
			$info->shipping = $vars['shipping'];
		}
		if(isset($vars['mc_gross'])) {
			$info->total = $vars['mc_gross'];
			$info->amount = $info->total - (isset($info->handling) ? $info->handling : 0) - (isset($info->shipping) ? $info->shipping : 0);
		}
		if(isset($vars['mc_fee'])) {
			$info->fee = $vars['mc_fee'];
		}
		if(isset($vars['mc_currency'])) {
			$info->currency = $vars['mc_currency'];
			$info->currencyCorrect = $info->currency == $this->settings->currency;
		}
		if(isset($vars['payment_date'])) {
			$info->date = strtotime($vars['payment_date']);
		}
		if(isset($vars['memo'])) {
			$info->note = $vars['memo'];
		}
		if(isset($vars['business'])) {
			$info->business = $vars['business'];
			$sandbox = isset($vars['test_ipn']) && $vars['test_ipn'] == 1;
			$info->businessCorrect = $info->business == $this->authentication->getEmail() && $sandbox == $this->authentication->isSandbox();
		}
		$info->resent = isset($vars['resend']) && $vars['resend'] == 'true';
		$info->buyer = $this->getBuyer($vars);
		
		$info->products = array();
		for($i = 1; isset($vars["item_name$i"]); $i++) {
			$info->products[] = $this->getProduct($vars, $i, $info);
		}
		if(isset($vars['item_name'])) {
			$info->products[] = $this->getProduct($vars, '', $info);
		}
		$info->ok = $info->businessCorrect && $info->currencyCorrect;
		return $info;
	}
	
	/**
	 * Get a PaypalProduct from $vars
	 * 
	 * @param array $vars
	 * @param string $number use when more than one product eg '1', '2'
	 * @param PaypalNotification
	 * @return PaypalProduct 
	 */
	private function getProduct($vars, $number = '', $info) {
		$product = new PaypalProduct();
		if(isset($vars["item_number$number"])) {
			$product->id = $vars["item_number$number"];
		}
		if(isset($vars["item_name$number"])) {
			$product->name = $vars["item_name$number"];
		}
		if(isset($vars["quantity$number"])) {
			$product->quantity = $vars["quantity$number"];
		}
		if(isset($vars["mc_shipping$number"])) {
			$product->shipppingTotal = $vars["mc_shipping$number"];
		}
		if(isset($vars["mc_handling$number"])) {
			$product->handling = $vars["mc_handling$number"];
		}
		if(isset($vars["mc_gross_$number"])) {
			$product->total = $vars["mc_gross_$number"];
			$product->amount = $product->total - (empty($product->shippingTotal) ? 0 : $product->shippingTotal) - (empty($product->handling) ? 0 : $product->handling);
		}
		else if(isset($vars["mc_gross$number"])) {
			$product->total = $vars["mc_gross$number"];
			$product->amount = $product->total - (empty($product->shippingTotal) ? 0 : $product->shippingTotal) - (empty($product->handling) ? 0 : $product->handling);
		}
		if(isset($vars["mc_fee_$number"])) {
			$product->fee = $vars["mc_fee_$number"];
		}
		else if(isset($vars["mc_fee$number"])) {
			$product->fee = $vars["mc_fee$number"];
		}
		else {
			$product->fee = ($product->total / $info->total) * $info->fee;
		}
		return $product;
	}

	/**
	 * Get a PaypalBuyer object from the $vars
	 * 
	 * @param array $vars
	 * @return PaypalBuyer 
	 */
	private function getBuyer($vars) {
		$buyer = new PaypalBuyer();
		if(isset($vars['payer_id'])) {
			$buyer->id = $vars['payer_id'];
		}
		if(isset($vars['first_name'])) {
			$buyer->firstName = $vars['first_name'];
		}
		if(isset($vars['last_name'])) {
			$buyer->lastName = $vars['last_name'];
		}
		if(isset($vars['payer_email'])) {
			$buyer->email = $vars['payer_email'];
		}
		if(isset($vars['payer_business_name'])) {
			$buyer->business = $vars['payer_business_name'];
		}
		if(isset($vars['contact_phone'])) {
			$buyer->phone = $vars['contact_phone'];
		}
		if(isset($vars['payer_status'])) {
			$buyer->status = $vars['payer_status'];
		}
		if(isset($vars['address_country'])) {
			$buyer->addressCountry = $vars['address_country'];
		}
		if(isset($vars['address_country_code'])) {
			$buyer->addressCountryCode = $vars['address_country_code'];
		}
		if(isset($vars['address_zip'])) {
			$buyer->addressZip = $vars['address_zip'];
		}
		if(isset($vars['address_state'])) {
			$buyer->addressState = $vars['address_state'];
		}
		if(isset($vars['address_city'])) {
			$buyer->addressCity = $vars['address_city'];
		}
		if(isset($vars['address_street'])) {
			$buyer->addressStreet = $vars['address_street'];
		}
		if(isset($vars['address_name'])) {
			$buyer->addressName = $vars['address_name'];
		}
		if(isset($vars['address_status'])) {
			$buyer->addressStatus = $vars['address_status'];
		}
		return $buyer;
	}
	
	/**
	 * Send a payment to an email address
	 * Users MassPayments API
	 * Warning, this works straight away, no confirming or anything
	 * Money is sent instantly
	 * 
	 * @param string|array $email one or more email addresses to send payment to
	 * @param string|array $amount amount to send to each address
	 * @param string|array $id unique id for the payment
	 * @param string|array $note note to the user
	 * @param string $subject subject for email
	 * @return bool whether succesful or not 
	 */
	public function sendPayment($email, $amount, $id = '', $note = '', $subject = null) {
		$params = array();
		$params['RECEIVERTYPE'] = 'EmailAddress';
		$params['EMAILSUBJECT'] = substr($subject, 0, 255);
		if(is_array($email)) {
			$count = count($email);
			
			$amountArray = is_array($amount);
			if($amountArray && count($amount) != $count) {
				return false;
			}
			
			$idArray = is_array($id);
			if($idArray && count($id) != $count) {
				return false;
			}
			
			$noteArray = is_array($note);
			if($noteArray && count($note) != $count) {
				return false;
			}
			
			for($i = 0;$i < $count; $i++) {
				$params["L_EMAIL$i"] = $email[$i];
				if($amountArray) {
					$params["L_AMT$i"] = $amount[$i];
				}
				else {
					$params["L_AMT$i"] = $amount;
				}
				
				if($idArray) {
					$params["L_UNIQUEID$i"] = substr($id[$i], 0, 30);
				}
				else {
					$params["L_UNIQUEID$i"] = substr($id, 0, 30);
				}
				
				if($noteArray) {
					$params["L_NOTE$i"] = substr($note[$i], 0, 4000);
				}
				else {
					$params["L_NOTE$i"] = substr($note, 0, 4000);
				}
			}
		}
		else {
			$params['L_EMAIL0'] = $email;
			$params['L_AMT0'] = $amount;
			$params['L_UNIQUEID0'] = substr($id, 0, 30);
			$params['L_NOTE0'] = substr($note, 0, 4000);
			
		}
		$params['CURRENCYCODE'] = $this->settings->currency;
		$response = $this->callPaypalNVP('MassPay', $params);
		if($response !== false) {
			if ($response['ACK'] == 'Success') {
				return true;
			}
			else {
				$this->error("MassPay failed " . $this->formatACKError($response));
				return false;
			}
		}
		else {
			return false;
		}
	}
	
	/**
	 * Refund the payment
	 * 
	 * @param string $transactionId id
	 * @param string $invoiceId optional internal payment id
	 * @param string $type currently only Full is supported
	 * @return bool successful
	 */
	public function refundPayment($transactionId, $invoiceId = null, $type = 'Full') {
		$params = array();
		$params['TRANSACTIONID'] = $transactionId;
		$params['INVOICEID'] = $invoiceId;
		$params['REFUNDTYPE'] = 'Full';
		$response = $this->callPaypalNVP('RefundTransaction', $params);
		if($response !== false) {
			if ($response['ACK'] == 'Success') {
				return true;
			}
			else {
				$this->error("RefundTransaction failed " . $this->formatACKError($response));
				return false;
			}
		}
		else {
			return false;
		}
	}
	
	/**
	 * Make a paypal NVP API call
	 * 
	 * @param string $method
	 * @param array $params
	 * @return array|bool the response vars as an assoc array or false on error
	 */
	private function callPaypalNVP($method, $params) {
		$headerParams = array();
		if ($this->authentication->isSandbox()) {
			$url = 'https://api-3t.sandbox.paypal.com/nvp';
		}
		else {
			$url = 'https://api-3t.paypal.com/nvp';
		}
		$headerParams['USER'] = $this->authentication->getUsername();
		$headerParams['PWD'] = $this->authentication->getPassword();
		$headerParams['SIGNATURE'] = $this->authentication->getSigniture();
		$headerParams['VERSION'] = '71.0';

		$data = "METHOD=" . urlencode($method) . '&' . $this->urlPairs($headerParams) . '&' . $this->urlPairs($params);
		
		$response = $this->makeRequest($url, $data);
		if($response === false) {
			return false;
		}
		
		$nvpResArray = $this->unUrlPairs($response);
		return $nvpResArray;
	}
	
	/**
	 * Verify paypal notification
	 * 
	 * @return bool
	 */
	private function verifyNotification($vars) {
		if ($this->authentication->isSandbox()) {
			$url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		}
		else {
			$url = 'https://www.paypal.com/cgi-bin/webscr';
		}
		$data = 'cmd=_notify-validate&' . $this->urlPairs($vars);
		$response = $this->makeRequest($url, $data);
		if($response === false) {
			return false;
		}
		$verified = $response == 'VERIFIED';
		if(!$verified) {
			$this->error('verify error, response' . $response);
		}
		return $verified;
	}
	
	/**
	 * Makes a request using curl, basicaly sets some curl options
	 * 
	 * @param string $url
	 * @param string $data
	 * @return string|bool returns false on error 
	 */
	private function makeRequest($url, $data) {
		//setting the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		//curl_setopt($ch, CURLOPT_VERBOSE, 1);
		//turning off the server and peer verification(TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);

		//setting the nvpreq as POST FIELD to curl
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

		//getting response from server
		$response = curl_exec($ch);
	
		if (curl_errno($ch)) {
			// moving to display page to display curl errors
			$this->error("Error posting to paypal " . curl_error($ch) . " url:$url data:$data");
			return false;
		}
		else {
			//closing the curl
			curl_close($ch);
		}
		return $response;
	}
	
	/**
	 * Format an error from paypal
	 * 
	 * @param array $response of string
	 * @return string
	 */
	private function formatACKError($response) {
		return "{$response['ACK']} {$response['L_SHORTMESSAGE0']} {$response['L_LONGMESSAGE0']}";
	}
	
	/**
	* Errors from all function come here to make it easy to change logging method
	*/
	private function error($message) {
		$this->lastError = $message;
		error_log($message);
	}
	
	/**
	* Get the message for the last error
	 * 
	* @return string
	*/
	public function getLastError() {
		return $this->lastError;
	}

	/**
	 * Make a url string ('x=y&z=a...') from the url
	 * urlencodes keys and values
	 * 
	 * @param array $array
	 * @return string
	 */
	private function urlPairs($array) {
		$out = array();
		foreach ($array as $key => $value) {
			$out[] = urlencode($key) . '=' . urlencode($value);
		}
		return implode('&', $out);
	}

	/**
	 * Make an array from a string of name value pairs ('x=y&z=a...')
	 * urldecodes keys and values
	 * 
	 * @param string $string
	 * @return array 
	 */
	private function unUrlPairs($string) {
		$intial = 0;
		$pairs = array();
		while (strlen($string)) {
			//postion of Key
			$keypos = strpos($string, '=');
			//position of value
			$valuepos = strpos($string, '&') ? strpos($string, '&') : strlen($string);

			/* getting the Key and Value values and storing in a Associative Array */
			$keyval = substr($string, $intial, $keypos);
			$valval = substr($string, $keypos + 1, $valuepos - $keypos - 1);
			//decoding the respose
			$pairs[urldecode($keyval)] = urldecode($valval);
			$string = substr($string, $valuepos + 1, strlen($string));
		}
		return $pairs;
	}
}

/**
 * Describes a notification
 */
class PaypalNotification {
	/**
	 * The business and currency are correct
	 * @var bool
	 */
	public $ok;
	
	/**
	 * Either {@link PaypalNotification::REFUND} or {@link PaypalNotification::CART}
	 * @var string
	 */
	public $type;
	/**
	 * type for refund notification
	 */
	const REFUND = 'refund';
	/**
	 * type for cart notification
	 */
	const CART = 'cart';
	/**
	 * type for masspay notification
	 */
	const MASSPAY = 'masspay';
	
	/**
	 * Type of transaction (paypal)
	 * @var string 
	 */
	public $transactionType;
	/**
	 * Paypals id for this transaction
	 * @var string
	 */
	public $transactionId;
	/**
	 * The origonal transaction (for refunds)
	 * @var string
	 */
	public $parentTransactionId;
	/**
	 * The invoice id you sent as part of this transaction
	 * @var int
	 */
	public $invoiceId;
	/**
	 * The custom data you sent
	 * @var string
	 */
	public $custom;
	/**
	 * The total amount paid
	 * You should check this is what you expect it to be
	 * @var double
	 */
	public $total;
	
	/**
	 * The amount paid minus shipping and handling
	 * @var double 
	 */
	public $amount;
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
	 * The paypal fee (you received {@link $amount}-{@link $fee})
	 * @var double
	 */
	public $fee;
	/**
	 * Note left by buyer (if you allowed him to leave it)
	 * @var string
	 */
	public $note;
	/**
	 * Who was paid
	 * @var string
	 */
	public $business;
	/**
	 * Was it the right person paid
	 * @var bool
	 */
	public $businessCorrect;
	
	/**
	 * Currency paid in
	 * @var string 
	 */
	public $currency;
	
	/**
	 * Is this the currency expected
	 * @var bool
	 */
	public $currencyCorrect;
	
	/**
	 * Status of payment
	 * The status of the payment: 
	 * <b>Canceled_Reversal:</b> A reversal has been canceled. For example, you
	 * won a dispute with the customer, and the funds for the transaction
	 * that was reversed have been returned to you.
	 * <b>Completed:</b> The payment has been completed,
	 * and the funds have been added successfully to your account balance.
	 * <b>Created:</b> A German ELV payment is made using Express Checkout.
	 * <b>Denied:</b> You denied the payment.
	 * This happens only if the payment was previously pending because of 
	 * possible reasons described for the pending_reason variable
	 * or the Fraud_Management_Filters_x variable.
	 * <b>Expired:</b> This authorization has expired and cannot be captured.
	 * <b>Failed:</b> The payment has failed.
	 * This happens only if the payment was made from your
	 * customer’s bank account.
	 * <b>Pending:</b> The payment is pending. See pending_reason for more information.
	 * <b>Refunded:</b> You refunded the payment.
	 * <b>Reversed:</b> A payment was reversed due to a chargeback or other type of
	 * reversal. The funds have been removed from your account balance
	 * and returned to the buyer. The reason for the reversal is
	 * specified in the ReasonCode element.
	 * <b>Processed:</b> A payment has been accepted.
	 * <b>Voided:</b> This authorization has been voided.
	 * @var string
	 */
	public $status;
	/**
	 * If $status == 'Pending' this is why
	 * @var string
	 */
	public $pendingReason;
	/**
	 * Time the payment was made (unix timestamp)
	 * @var int
	 */
	public $date;
	/**
	 * Has this notification been sent before
	 * @var bool
	 */
	public $resent;
	
	/**
	 * Info about buyer
	 * @var PaypalBuyer
	 */
	public $buyer;
	
	/**
	 * The product purchased in this transaction
	 * Can be used to check the right amounts where paid and the cart is what you expected
	 * @var array {@link PaypalProduct}
	 */
	public $products;
	
	/**
	 * Sub notifications, used by masspay
	 * @var array {@link PaypalNotification}
	 */
	public $notifications;
}

/**
 * Describes the buyer of your product, this info is received in notifications
 */
class PaypalBuyer {
	/**
	 * Unique id for this buyer
	 * @var string
	 */
	public $id;
	/**
	 * First name
	 * @var string
	 */
	public $firstName;
	/**
	 * Last name
	 * @var string
	 */
	public $lastName;
	/**
	 * Email
	 * @var string
	 */
	public $email;
	/**
	 * business name if has one
	 * @var string
	 */
	public $business;
	/**
	 * contact phone
	 * @var string
	 */
	public $phone;
	/**
	 * status, ie verified or not
	 * @var string
	 */
	public $status;
	/**
	 * Country
	 * @var string
	 */
	public $addressCountry;
	/**
	 * Country code ie gb or fr etc
	 * @var string 
	 */
	public $addressCountryCode;
	/**
	 * Zip code or similar
	 * @var string
	 */
	public $addressZip;
	/**
	 * State
	 * @var string 
	 */
	public $addressState;
	/**
	 * City
	 * @var string 
	 */
	public $addressCity;
	/**
	 * Street
	 * @var string
	 */
	public $addressStreet;
	/**
	 * Name to be used with address
	 * @var string
	 */
	public $addressName;
	/**
	 * status of address, ie verifed or not
	 * @var string 
	 */
	public $addressStatus;
}

/**
 * Describes a product to be sold
 * Some vars effect selling
 * Some are set when receiving a notification
 */
class PaypalProduct {
	/**
	 * unique id of this product
	 * @var int
	 */
	public $id;
	/**
	 * name of product
	 * @var string
	 */
	public $name;
	/**
	 * quanitity of product to sell
	 * @var int
	 */
	public $quantity;
	/**
	 * overall handling fee for these items
	 * @var double
	 */
	public $handling;
	
	/**
	 * Cost per item
	 * @var double
	 */
	public $amount;
	/**
	 * discount given for all these items
	 * @var double
	 */
	public $discount;
	/**
	 * tax for all these items
	 * @var double
	 */
	public $tax;
	/**
	 * cost of shipping the first of these items
	 * @var double
	 */
	public $shipping;
	/**
	 * cost of shipping futher items 
	 * @var double
	 */
	public $shipping2;
	/**
	 * weight of this item if your accout is setup to use weight base shipping
	 * @var double
	 */
	public $weight;
	
	/**
	 * total amount paid for these items
	 * Set when received by notification
	 * @var double
	 */
	public $total;
	/**
	 * total amount of shipping paid for these items
	 * Set when received by notification
	 * @var double
	 */
	public $shipppingTotal;
	/**
	 * total fee paid to paypal for these items
	 * Set when received by notification
	 * (ie you received {@link $total}-{@link $fee})
	 * @var double
	 */
	public $fee;
}

/**
 * Settings used in paypal transactions
 */
class PaypalSettings {
	
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
	
	public $logNotifications = false;
	
}

/**
* Class with read only vars for using paypal api's
*/
class PaypalAuthenticaton {
	private $email;
	private $username;
	private $password;
	private $signiture;
	private $sandbox;
	
	public function __construct($email, $username, $password, $signiture, $sandbox = false) {
		$this->email = $email;
		$this->username = $username;
		$this->password = $password;
		$this->signiture = $signiture;
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
	
	public function getSigniture() {
		return $this->signiture;
	}
	
	public function isSandbox() {
		return $this->sandbox;
	}
}
?>