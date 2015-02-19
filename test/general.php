<?php
require 'common.php';

//Create the authentication
$auth = new Mcfedr\Paypal\Authentication('seller_1305978152_biz@gmail.com',
    'seller_1305978152_biz_api1.gmail.com',
    '1305978161',
    'ANuWCqPKdl8pa4WYHr9g0kh6hysAAw2yhHObLujRQpilgNH0uanFiO3x',
    true);
//Create the paypal object
$paypal = new Mcfedr\Paypal\Paypal($auth);

//the base url
$me = "http://{$_SERVER['HTTP_HOST']}/{$_SERVER['REQUEST_URI']}";

//find out what we are doing, the default is start
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'start';

if ($action == 'notify') {
    $notification = $paypal->handleNotification();
    //log the notification
    error_log("Notification: " . var_export($notification, true));
    exit;
}
?>
    <!DOCTYPE html>
    <html>
    <head>
        <title><?php echo $action ?></title>
    </head>
    <body>
    <?php
    if ($action == 'cancel') {
        echo "<h1>cancel</h1>";
        echo "<p>You cancelled a paypal</p>";
    } else {
        if ($action == 'paid') {
            echo "<h1>paid</h1>";
            echo "<p>You completed a payment</p>";
        } else {
            if ($action == 'refund' && isset($_REQUEST['txn_id'])) {
                //refund the payment
                $refunded = $paypal->refundPayment($_REQUEST['txn_id']);
                echo "<h1>refund</h1>";
                if ($refunded) {
                    echo "<p>You refunded the payment</p>";
                } else {
                    echo "<p>There was an error processing the refund</p>";
                    echo "<p>" . $paypal->getLastError() . "</p>";
                }
            } else {
                if ($action == 'send') {
                    $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
                    $amount = isset($_REQUEST['amount']) ? $_REQUEST['amount'] : '';
                    $emails = explode(',', $email);
                    $amounts = explode(',', $amount);
                    $sent = $paypal->sendPayment($emails, $amounts);
                    echo "<h1>send</h1>";
                    if ($sent) {
                        echo "<p>You sent the payment(s)</p>";
                    } else {
                        echo "<p>There was an error sending the payment(s)</p>";
                        echo "<p>" . $paypal->getLastError() . "</p>";
                    }
                }
            }
        }
    }
    //create a product
    $product = new Mcfedr\Paypal\Products\CartProduct();
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
    $product2 = new Mcfedr\Paypal\Products\CartProduct();
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
    echo "<h1>checkout</h1>";
    echo "<p>" . var_export($products, true) . "</p>";

    //get button action
    $formAction = $paypal->getButtonAction();
    //get params for the form
    $params = $paypal->getButtonParams($products, "$me?action=paid", "$me?action=cancel", "$me?action=notify");

    //create a form with these params
    echo "<form action=\"$formAction\" method=\"post\">";
    foreach ($params as $key => $value) {
        echo "<input type=\"hidden\" name=\"$key\" value=\"$value\"/>";
    }
    echo "<button type=\"submit\">Checkout</button>";
    echo "</form>";

    $subscription = new Mcfedr\Paypal\Products\Subscription();
    $subscription->id = 1;
    $subscription->name = 'Magazine';
    $subscription->amount = 100;
    $subscription->duration = 1;
    $subscription->units = Mcfedr\Paypal\Products\Subscription::MONTHS;
    $subscription->generateUsernameAndPassword = true;
    $subscription->reattempt = true;
    $subscription->recuring = true;
    $subscription->trialAmount = 50;
    $subscription->trialDuration = 2;
    $subscription->trialUnits = Mcfedr\Paypal\Products\Subscription::WEEKS;

    //so we can see these products
    echo "<h1>subscribe</h1>";
    echo "<p>" . var_export($subscription, true) . "</p>";

    //get params for the form
    $subParams = $paypal->getButtonParams($subscription, "$me?action=paid", "$me?action=cancel", "$me?action=notify");

    //create a form with these params
    echo "<form action=\"$formAction\" method=\"post\">";
    foreach ($subParams as $key => $value) {
        echo "<input type=\"hidden\" name=\"$key\" value=\"$value\"/>";
    }
    echo "<button type=\"submit\">Subscribe</button>";
    echo "</form>";

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
function printVars($vars)
{
    echo "<table><thead><tr><th>key</th><th>value</th></thead><tbody>";
    foreach ($vars as $key => $value) {
        echo "<tr><td>$key</td><td>$value</td></tr>";
    }
    echo "</tbody></table>";
}
