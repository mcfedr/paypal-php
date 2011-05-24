<?php
/**
 * For accessing the paypal api
 * See the test file for examples of usage
 * 
 * @author Fred Cox <mcfedr@gmail.com>
 * @copyright Copyright Fred Cox, 2011
 * @package paypal-php
 * @subpackage libpaypal
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
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
	 * @param PaypalProduct|array $products of PaypalProduct
	 * @param string $paidURL URL paypal returns user to when completed
	 * @param string $cancelURL URL to return to if user cancels payment
	 * @param string $notifyURL URL for instant notifications
	 * @param int $invoiceId Your id for this payment, must be unique
	 * @param string $custom Custom data that will be in any notifications
	 * @param string label Label for the button
	 * @return string html form with a button
	 */
	public function getButton($products, $paidURL, $cancelURL, $notifyURL = null, $invoiceId = null, $custom = null, $label = "Checkout") {
		$action = $this->getButtonAction();
		$params = $this->getButtonParams($products, $paidURL, $cancelURL, $notifyURL, $invoiceId, $custom);
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
	 * @param PaypalProduct|array $products of PaypalProduct
	 * @param string $paidURL URL paypal returns user to when completed
	 * @param string $cancelURL URL to return to if user cancels payment
	 * @param string $notifyURL URL for instant notifications
	 * @param int $invoiceId Your id for this payment, must be unique
	 * @param string $custom Custom data that will be in any notifications
	 * @return array of string 
	 */
	public function getButtonParams($products, $paidURL, $cancelURL, $notifyURL = null, $invoiceId = null, $custom = null) {
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
		
		//$params['email'] = $email;
		//$space = strpos($name, ' ');
		//$params['first_name'] = $space === false ? $name : substr($name, 0, $space);
		//$params['last_name'] = $space === false ? '' : substr($name, $space + 1);
		
		$this->setSettingsParams($this->settings, $params);
		
		$params['business'] = $this->authentication->getEmail();
		return $params;
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
			$handled = $this->getNotification($vars);
			if(isset($vars['txn_type'])) {
				switch($vars['txn_type']) {
					case 'cart':
					case 'web_accept':
						$handled->type = PaypalNotification::CART;
						break;
				}
			}
			else if(isset($vars['payment_status'])) {
				switch($vars['payment_status']) {
					case 'Refunded':
					case 'Reversed':
					case 'Canceled_Reversal':
						$handled->type = PaypalNotification::REFUND;;
						break;
				}
			}
			$handled->ok = $handled->businessCorrect && $handled->currencyCorrect && isset($handled->type);
		}
		//if(!$handled->ok) {
			$this->error(($verified ? '' : 'Unverified ') . ($handled->ok ? '' : 'Unhandled ') . 'paypal notification ' . $this->urlPairs($vars));
		//}
		return $handled;
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
		if(isset($vars['mc_gross'])) {
			$info->amount = $vars['mc_gross'];
		}
		if(isset($vars['mc_handling'])) {
			$info->handling = $vars['mc_handling'];
		}
		if(isset($vars['mc_shipping'])) {
			$info->shipping = $vars['mc_shipping'];
		}
		if(isset($vars['mc_fee'])) {
			$info->fee = $vars['mc_fee'];
		}
		if(isset($vars['mc_currency'])) {
			$info->currency = $vars['mc_currency'];
			$info->currencyCorrect = $vars['mc_currency'] == $this->settings->currency;
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
		if(isset($vars["item_name1"])) {
			$i = 1;
			while(isset($vars["item_name$i"])) {
				$info->products[] = $this->getProduct($vars, $i);
				$i++;
			}
		}
		else if(isset($vars['item_name'])) {
			$info->products[] = $this->getProduct($vars);
		}
		return $info;
	}
	
	/**
	 * Get a PaypalProduct from $vars
	 * 
	 * @param array $vars
	 * @param string $number use when more than one product eg '1', '2'
	 * @return PaypalProduct 
	 */
	private function getProduct($vars, $number = '') {
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
		if(isset($vars["mc_fee$number"])) {
			$product->fee = $vars["mc_fee$number"];
		}
		if(isset($vars["mc_gross_$number"])) {
			$product->total = $vars["mc_gross_$number"];
			$product->amount = $product->total - (empty($product->shippingTotal) ? 0 : $product->shippingTotal) - (empty($product->handling) ? 0 : $product->handling);
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
	 * @return bool whether succesful or not 
	 */
	public function sendPayment($email, $amount) {
		$params = array();
		$params['RECEIVERTYPE'] = 'EmailAddress';
		if(is_array($email) || is_array($amount)) {
			if(($count = count($email)) == count($amount)) {
				for($i = 0;$i < $count; $i++) {
					$params["L_EMAIL$i"] = $email[$i];
					$params["L_AMT$i"] = $amount[$i];
				}
			}
			else {
				return false;
			}
		}
		else {
			$params['L_EMAIL0'] = $email;
			$params['L_AMT0'] = $amount;
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


class PaypalNotification {
	public $ok;
	
	public $type;
	const REFUND = 'refund';
	const CART = 'cart';
	
	public $transactionType;
	public $transactionId;
	public $parentTransactionId;
	public $invoiceId;
	public $custom;
	public $amount;
	public $shipping;
	public $handling;
	public $fee;
	public $note;
	public $business;
	public $businessCorrect;
	public $status;
	public $pendingReason;
	public $date;
	public $resent;
	
	/**
	* @var PaypalBuyer
	*/
	public $buyer;
	/**
	* @var array of PaypalProduct
	*/
	public $products;
}

class PaypalBuyer {
	public $id;
	public $firstName;
	public $lastName;
	public $email;
	public $business;
	public $phone;
	public $status;
	public $addressCountry;
	public $addressCountryCode;
	public $addressZip;
	public $addressState;
	public $addressCity;
	public $addressStreet;
	public $addressName;
	public $addressStatus;
}

class PaypalProduct {
	public $id;
	public $name;
	public $quantity;
	public $handling; //overall handling for these items
	/**
	* Vars for button creation
	*/
	public $amount; //per item
	public $discount; //overall discount for these items
	public $tax; //overall tax for these items
	public $shipping; //shipping for first item
	public $shipping2; //shipping for futher items
	public $weight;
	
	/**
	* Vars for notifications
	*/
	public $total;
	public $shipppingTotal;
	public $fee;
}

class PaypalSettings {
	public $currency = 'GBP';
	public $local = 'GB';
	public $cancelBtn = 'Return to merchant';
	public $canChooseQuantity = false;
	public $shippingInfoNeeded = false;
	public $allowMerchantNote = false;
	public $imageURL = null;
	public $payflowColor = null;
	public $headerBackgroundColor = null;
	public $headerBorderColor = null;
	public $weightUnit = null;
}

/**
* Class with read only vars for using paypal api's
*/
class PaypalAuthenticaton {
	private $email;
	private $username;
	private $password;
	private $signiture;
	
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