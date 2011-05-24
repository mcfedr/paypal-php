<?php
/**
 * This file tests most of the functionality of libpaypal
 * Host it somewhere that paypal can see it to test notifications
 * which will be logged in php's default log file
 * 
 * @author Fred Cox <mcfedr@gmail.com>
 * @copyright Copyright Fred Cox, 2011
 * @package paypal-php
 * @subpackage test
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
require_once('../lib/libpaypal.php');

//Create the authentication
$auth = new PaypalAuthenticaton('seller_1305978152_biz@gmail.com', 
								'seller_1305978152_biz_api1.gmail.com', 
								'1305978161', 
								'ANuWCqPKdl8pa4WYHr9g0kh6hysAAw2yhHObLujRQpilgNH0uanFiO3x',
								true);
//Create the paypal object
$paypal = new Paypal($auth);

//the base url
$me = "http://mcfedr.dnsdojo.net/~mcfedr/paypal/test/";

//find out what we are doing, the default is start
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'start';

if($action == 'notify') {
	$notification = $paypal->handleNotification();
	//log the notification
	error_log("Notification: " .var_export($notification, true));
	exit;
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title><?php $action?></title>
	</head>
	<body>
		<?php
		if($action == 'cancel') {
			echo "<h1>cancel</h1>";
			echo "<p>You cancelled a paypal</p>";
		}
		else if($action == 'paid') {
			echo "<h1>paid</h1>";
			echo "<p>You completed a payment</p>";
		}
		else if($action == 'refund' && isset($_REQUEST['txn_id'])) {
			//refund the payment
			$refunded = $paypal->refundPayment($_REQUEST['txn_id']);
			echo "<h1>refund</h1>";
			if($refunded) {
				echo "<p>You refunded the payment</p>";
			}
			else {
				echo "<p>There was an error processing the refund</p>";
				echo "<p>" . $paypal->getLastError() . "</p>";
			}
		}
		else if($action == 'send') {
			$email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
			$amount = isset($_REQUEST['amount']) ? $_REQUEST['amount'] : '';
			$emails = explode(',', $email);
			$amounts = explode(',', $amount);
			$sent = $paypal->sendPayment($emails, $amounts);
			echo "<h1>send</h1>";
			if($sent) {
				echo "<p>You sent the payment(s)</p>";
			}
			else {
				echo "<p>There was an error sending the payment(s)</p>";
				echo "<p>" . $paypal->getLastError() . "</p>";
			}
		}
		//create a product
		$product = new PaypalProduct();
		$product->id = 1;
		$product->name = "Book";
		$product->amount = 10;
		$product->discount = 2;
		$product->quantity = 3;
		$product->tax = 1;
		$product->shipping = 1;
		$product->shipping2 = 2;
		$product->handling = 1;
		
		//and another
		$product2 = new PaypalProduct();
		$product2->id = 2;
		$product2->name = "CD";
		$product2->amount = 10;
		$product2->discount = 5;
		$product2->quantity = 10;
		$product2->tax = 1;
		$product2->shipping = 1;
		$product2->shipping2 = 2;
		$product2->handling = 1;
		
		//put them together
		$products = array($product, $product2);
		//or just use one
		//$products = $product;
		
		//so we can see these products
		echo "<h1>form</h1>";
		echo "<p>".var_export($products, true)."</p>";
		
		//get button action
		$action = $paypal->getButtonAction();
		//get params for the form
		$params = $paypal->getButtonParams($products, "$me?action=paid", "$me?action=cancel", "$me?action=notify");
		
		//create a form with these params
		$ret = "<form action=\"$action\" method=\"post\">";
		foreach($params as $key => $value) {
			$ret .= "<input type=\"hidden\" name=\"$key\" value=\"$value\"/>";
		}
		$ret .= "<button type=\"submit\">Checkout</button>";
		$ret .= "</form>";
		echo $ret;
		
		//refund form
		echo "<h1>refund</h1>";
		echo "<form action=\"$me\" method=\"post\">";
		//default is the last txn_id
		$txn_id = isset($_REQUEST['txn_id']) ? $_REQUEST['txn_id'] : '';
		echo "<input type=\"hidden\" name=\"action\" value=\"refund\"/>";
		echo "<label for=\"txn_id\">Transaction ID</label>";
		echo "<input type=\"text\" id=\"txn_id\" name=\"txn_id\" value=\"$txn_id\"/>";
		echo "<button type=\"submit\">Refund</button>";
		echo "</form>";
		
		//send form
		echo "<h1>send payment</h1>";
		echo "<form action=\"$me\" method=\"post\">";
		echo "<input type=\"hidden\" name=\"action\" value=\"send\"/>";
		$email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
		echo "<label for=\"email\">Email(s)</label>";
		echo "<input type=\"text\" id=\"email\" name=\"email\" value=\"$email\"/>";
		$amount = isset($_REQUEST['amount']) ? $_REQUEST['amount'] : '';
		echo "<label for=\"amount\">Amount</label>";
		echo "<input type=\"text\" id=\"amount\" name=\"amount\" value=\"$amount\">";
		echo "<button type=\"submit\">Send</button>";
		echo "</form>";
		
		//print everything so we can see it
		echo "<h1>form vars</h1>";
		echo printVars($params);
		echo "<h1>request</h1>";
		echo printVars($_REQUEST);
		?>
	</body>
</html>
<?php
/**
 * Prints an array as a html table
 * @param array $vars 
 */
function printVars($vars) {
	echo "<table><thead><tr><th>key</th><th>value</th></thead><tbody>";
	foreach($vars as $key => $value) {
		echo "<tr><td>$key</td><td>$value</td></tr>";
	}
	echo "</tbody></table>";
}
?>