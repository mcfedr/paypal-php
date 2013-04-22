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
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 */
namespace Paypal;

/**
 * This class is used for generation of buttons and handling of paypal responses
 */
class Paypal {
	
	/**
	* Authentication to use
	* @var Authentication
	*/
	private $authentication;
	
	/**
	* Settings to use
	* @var Settings
	*/
	private $settings;
	
	/**
	 * Create a new paypal object
	 * 
	 * @param Authentication $authentication 
	 * @param Settings $settings 
	 */
	public function __construct(Authentication $authentication, Settings $settings = null) {
		$this->authentication = $authentication;
		if($settings == null) {
			$settings = new Settings();
		}
		$this->settings = $settings;
	}
	
	/**
	 * Get a button as html
	 * Use getButtonParams and getButtonAction if you need to customise
	 *
	 * @param Products\Subscription|Products\CartProduct|Products\CartProduct[] $products
	 * @param string $paidURL URL paypal returns user to when completed
	 * @param string $cancelURL URL to return to if user cancels payment
	 * @param string $notifyURL URL for instant notifications
	 * @param int $invoiceId Your id for this payment, must be unique
	 * @param string $custom Custom data that will be in any notifications
	 * @param Buyer $buyer Info about the buyer to autofill in
	 * @param string label Label for the button
	 * @return string html form with a button
	 */
	public function getButton($products, $paidURL, $cancelURL, $notifyURL = null, $invoiceId = null, $custom = null, Buyer $buyer = null, $label = "Checkout") {
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
	 * @param Products\Subscription|Products\CartProduct|Products\CartProduct[] $products
	 * @param string $paidURL URL paypal returns user to when completed
	 * @param string $cancelURL URL to return to if user cancels payment
	 * @param string $notifyURL URL for instant notifications
	 * @param int $invoiceId Your id for this payment, must be unique
	 * @param string $custom Custom data that will be in any notifications
	 * @param Buyer $buyer Info about the buyer to autofill in
	 * @return array of string 
	 */
	public function getButtonParams($products, $paidURL, $cancelURL, $notifyURL = null, $invoiceId = null, $custom = null, Buyer $buyer = null) {
		$params = array();
		if($products instanceof Products\Subscription) {
			$params['cmd'] = '_xclick-subscriptions';
			$products->setParams($params);
		}
		else if(!is_array($products) || count($products) == 1) {
			if(!is_array($products)) {
				$product = $products;
			}
			else {
				$product = $products[0];
			}
			$params['cmd'] = '_xclick';
			$product->setParams($params);
		}
		else {
			$params['cmd'] = '_cart';
			$params['upload'] = 1;
			$i = 1;
			foreach($products as $product) {
				$product->setParams($params, "_$i");
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
			$buyer->setParams($params);
		}
		
		$this->settings->setParams($params);
		
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
	* Call this function on your instant notification url (IN)
	* And success url to use payment data transfer (PDT)
	
	* @throws Exceptions\CurlException
	* @throws Exceptions\NotificationVerifiationException
	* @throws Exceptions\NotificationInvalidException
	* @param array $vars variables to use, normally $_POST
	* @return Notification
	*/
	public function handleNotification($vars = null) {
		if(is_null($vars)) {
			$vars = $_REQUEST;
		}
		$handled = false;
		
        if(isset($vars['txn_type'])) {
            switch($vars['txn_type']) {
                case Notifications\Notification::TXT_CART:
                case Notifications\Notification::TXT_WEB_ACCEPT:
                    $handled = new Notifications\CartNotification($vars);
                    break;
                case Notifications\Notification::TXT_MASSPAY:
                    $handled = new Notifications\MasspayNotification($vars);
                    break;
                case Notifications\Notification::TXT_SUBSCRIPTION_CANCEL:
                case Notifications\Notification::TXT_SUBSCRIPTION_EXPIRE:
                case Notifications\Notification::TXT_SUBSCRIPTION_FAILED:
                case Notifications\Notification::TXT_SUBSCRIPTION_MODIFY:
                case Notifications\Notification::TXT_SUBSCRIPTION_PAYMENT:
                case Notifications\Notification::TXT_SUBSCRIPTION_START:
                    $handled = new Notifications\SubscriptionNotification($vars);
                    break;
            }
        }
        else if(isset($vars['payment_status'])) {
            switch($vars['payment_status']) {
                case 'Refunded':
                case 'Reversed':
                case 'Canceled_Reversal':
                    $handled = new Notifications\CartChangeNotification($vars);
                    break;
            }
		}
        
		if(!$handled) {
			return false;
		}
        
        if(!$this->verifyNotification($vars)) {
            return false;
        }
		
		if(!$handled->isOK($this->authentication, $this->settings)) {
			throw new Exceptions\NotificationInvalidException($handled);
		}
		
		if($this->settings->logNotifications) {
			error_log('paypal notification ' . http_build_query($vars));
		}
		return $handled;
	}
	
	/**
	 * Send a payment to an email address
	 * Users MassPayments API
	 * Warning, this works straight away, no confirming or anything
	 * Money is sent instantly
	 * 
	 * @throws Exceptions\CurlException
	 * @throws Exceptions\MasspayException
	 * @param string|string[] $email one or more email addresses to send payment to
	 * @param double|double[] $amount amount(s) to send to each address
	 * @param string|string[] $id unique id for the payment(s)
	 * @param string|string[] $note note to the user(s)
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
				throw new Exceptions\MasspayException($response);
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
	 * @throws Exceptions\RefundException
	 * @throws Exceptions\CurlException
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
				throw new Exceptions\RefundException($response);
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
	 * @throws Exceptions\CurlException
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
		
		$data = http_build_query(array_merge(array('METHOD' => $method), $headerParams, $params));
		
		$response = $this->makeRequest($url, $data);
		if($response === false) {
			return false;
		}
		
		parse_str($response, $nvpResArray);
		return $nvpResArray;
	}
	
	/**
	 * Verify paypal notification
	 * 
	 * @throws Excetions\NotificationException
	 * @throws Exceptions\CurlException
	 * @return bool
	 */
	private function verifyNotification($vars) {
		if ($this->authentication->isSandbox()) {
			$url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		}
		else {
			$url = 'https://www.paypal.com/cgi-bin/webscr';
		}
		$data = http_build_query(array_merge(array('cmd' => '_notify-validate'), $vars));
		$response = $this->makeRequest($url, $data);
		if($response === false) {
			return false;
		}
		$verified = $response == 'VERIFIED';
		if(!$verified) {
			throw new Exceptions\NotificationVerifiationException($response);
		}
		return $verified;
	}
	
	/**
	 * Makes a request using curl, basicaly sets some curl options
	 * 
	 * @throws Exceptions\CurlException
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
			throw new Exceptions\CurlException($ch, $url, $data);
			return false;
		}
		else {
			//closing the curl
			curl_close($ch);
		}
		return $response;
	}
}
