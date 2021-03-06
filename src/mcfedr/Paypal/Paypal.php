<?php

namespace Mcfedr\Paypal;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Mcfedr\Paypal\Exceptions\UnsupportedRefundException;
use Mcfedr\Paypal\Products\Product;

/**
 * This class is used for generation of buttons and handling of paypal responses
 */
class Paypal
{

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
     * @var Client
     */
    private $client;

    /**
     * Create a new paypal object
     *
     * @param Authentication $authentication
     * @param Settings $settings
     */
    public function __construct(Authentication $authentication, Settings $settings = null)
    {
        $this->authentication = $authentication;
        if ($settings == null) {
            $settings = new Settings();
        }
        $this->settings = $settings;

        $this->client = new Client();
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
     * @param string $label Label for the button
     * @return string html form with a button
     */
    public function getButton(
        $products,
        $paidURL,
        $cancelURL,
        $notifyURL = null,
        $invoiceId = null,
        $custom = null,
        Buyer $buyer = null,
        $label = "Checkout"
    ) {
        $action = $this->getButtonAction();
        $params = $this->getButtonParams($products, $paidURL, $cancelURL, $notifyURL, $invoiceId, $custom, $buyer);
        $ret = "<form action=\"$action\" method=\"post\">";
        foreach ($params as $key => $value) {
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
    public function getButtonParams(
        $products,
        $paidURL,
        $cancelURL,
        $notifyURL = null,
        $invoiceId = null,
        $custom = null,
        Buyer $buyer = null
    ) {
        $params = [];
        if ($products instanceof Products\Subscription) {
            $params['cmd'] = '_xclick-subscriptions';
            $products->setParams($params);
        } else {
            if (!is_array($products) || count($products) == 1) {
                if (!is_array($products)) {
                    $product = $products;
                } else {
                    $product = $products[0];
                }
                $params['cmd'] = '_xclick';
                $product->setParams($params);
            } else {
                $params['cmd'] = '_cart';
                $params['upload'] = 1;
                $i = 1;
                /** @var Product $product */
                foreach ($products as $product) {
                    $product->setParams($params, "_$i");
                    $i++;
                }
            }
        }

        $params['return'] = $paidURL;
        $params['cancel_return'] = $cancelURL;

        if (!empty($notifyURL)) {
            $params['notify_url'] = $notifyURL;
        }
        if (!empty($custom)) {
            $params['custom'] = $custom;
        }
        if (!empty($invoiceId)) {
            $params['invoice'] = $invoiceId;
        }

        if (!empty($buyer)) {
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
    public function getButtonAction()
    {
        if ($this->authentication->isSandbox()) {
            return 'https://www.sandbox.paypal.com/cgi-bin/webscr';
        } else {
            return 'https://www.paypal.com/cgi-bin/webscr';
        }
    }

    /**
     * Call this function on your instant notification url (IN)
     * And success url to use payment data transfer (PDT)
     * @throws Exceptions\CurlException
     * @throws Exceptions\NotificationVerificationException
     * @throws Exceptions\NotificationInvalidException
     * @param array $vars variables to use, normally $_POST
     * @return Notifications\Notification
     */
    public function handleNotification($vars = null)
    {
        if (is_null($vars)) {
            $vars = $_REQUEST;
        }
        $handled = false;

        if (isset($vars['txn_type'])) {
            switch ($vars['txn_type']) {
                case Notifications\Notification::TXT_CART:
                case Notifications\Notification::TXT_WEB_ACCEPT:
                    $handled = new Notifications\CartNotification($vars);
                    break;
                case Notifications\Notification::TXT_MASSPAY:
                    $handled = new Notifications\MasspayNotifications($vars);
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
        } else {
            if (isset($vars['payment_status'])) {
                switch ($vars['payment_status']) {
                    case 'Refunded':
                    case 'Reversed':
                    case 'Canceled_Reversal':
                        $handled = new Notifications\CartChangeNotification($vars);
                        break;
                }
            } else {
                if (isset($vars['transaction_type'])) {
                    switch ($vars['transaction_type']) {
                        case Notifications\Notification::TXT_ADAPTIVE_CREATE:
                        case Notifications\Notification::TXT_ADAPTIVE_ADJUSTMENT:
                            $handled = new Notifications\AdaptivePaymentNotification($vars);
                            break;
                    }
                }
            }
        }

        if (!$handled) {
            return false;
        }

        if (!$this->verifyNotification($handled, $vars)) {
            return false;
        }

        //Check if it is ok, if not an exception will be thrown
        $handled->isOK($this->authentication, $this->settings);

        if ($this->settings->logNotifications === true) {
            error_log('paypal notification ' . http_build_query($vars));
        } else {
            if ($this->settings->logNotifications) {
                $this->settings->logNotifications->info('Paypal Notification', [
                    'vars' => $vars
                ]);
            }
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
    public function sendPayment($email, $amount, $id = '', $note = '', $subject = null)
    {
        $params = [];
        $params['RECEIVERTYPE'] = 'EmailAddress';
        $params['EMAILSUBJECT'] = substr($subject, 0, 255);
        if (is_array($email)) {
            $count = count($email);

            $amountArray = is_array($amount);
            if ($amountArray && count($amount) != $count) {
                return false;
            }

            $idArray = is_array($id);
            if ($idArray && count($id) != $count) {
                return false;
            }

            $noteArray = is_array($note);
            if ($noteArray && count($note) != $count) {
                return false;
            }

            for ($i = 0; $i < $count; $i++) {
                $params["L_EMAIL$i"] = $email[$i];
                if ($amountArray) {
                    $params["L_AMT$i"] = $amount[$i];
                } else {
                    $params["L_AMT$i"] = $amount;
                }

                if ($idArray) {
                    $params["L_UNIQUEID$i"] = substr($id[$i], 0, 30);
                } else {
                    $params["L_UNIQUEID$i"] = substr($id, 0, 30);
                }

                if ($noteArray) {
                    $params["L_NOTE$i"] = substr($note[$i], 0, 4000);
                } else {
                    $params["L_NOTE$i"] = substr($note, 0, 4000);
                }
            }
        } else {
            $params['L_EMAIL0'] = $email;
            $params['L_AMT0'] = $amount;
            $params['L_UNIQUEID0'] = substr($id, 0, 30);
            $params['L_NOTE0'] = substr($note, 0, 4000);
        }
        $params['CURRENCYCODE'] = $this->settings->currency;
        $response = $this->callPaypalNVP('MassPay', $params);
        if ($response !== false) {
            if ($response['ACK'] == 'Success') {
                return true;
            } else {
                throw new Exceptions\MasspayException($response);
            }
        } else {
            return false;
        }
    }

    /**
     * Refund the payment
     *
     *
     * @param string $transactionId id
     * @param string $invoiceId optional internal payment id
     * @param string $type currently only Full is supported
     * @throws Exceptions\RefundException
     * @throws Exceptions\UnsupportedRefundException
     * @return bool successful
     */
    public function refundPayment($transactionId, $invoiceId = null, $type = 'Full')
    {
        $params = [];
        $params['TRANSACTIONID'] = $transactionId;
        $params['INVOICEID'] = $invoiceId;
        if ($type != 'Full') {
            throw new UnsupportedRefundException($type);
        }
        $params['REFUNDTYPE'] = $type;
        $response = $this->callPaypalNVP('RefundTransaction', $params);
        if ($response !== false) {
            if ($response['ACK'] == 'Success') {
                return true;
            } else {
                throw new Exceptions\RefundException($response);
            }
        } else {
            return false;
        }
    }

    /**
     * Make a paypal NVP API call
     *
     * @throws RequestException
     * @param string $method
     * @param array $params
     * @return array|bool the response vars as an assoc array or false on error
     */
    private function callPaypalNVP($method, $params)
    {
        if ($this->authentication->isSandbox()) {
            $url = 'https://api-3t.sandbox.paypal.com/nvp';
        } else {
            $url = 'https://api-3t.paypal.com/nvp';
        }

        $headerParams = [];
        $headerParams['USER'] = $this->authentication->getUsername();
        $headerParams['PWD'] = $this->authentication->getPassword();
        $headerParams['SIGNATURE'] = $this->authentication->getSignature();
        $headerParams['VERSION'] = '71.0';

        $response = $this->client->post($url, [
            'body' => array_merge(['METHOD' => $method], $headerParams, $params)
        ]);

        parse_str(((string)$response->getBody()), $nvpResArray);
        return $nvpResArray;
    }

    /**
     * Verify paypal notification
     *
     *
     * @param \Mcfedr\Paypal\Notifications\Notification $notification
     * @param array $vars
     * @throws Exceptions\NotificationVerificationException
     * @throws RequestException
     * @return bool
     */
    private function verifyNotification($notification, $vars)
    {
        if ($this->authentication->isSandbox()) {
            $url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
        } else {
            $url = 'https://www.paypal.com/cgi-bin/webscr';
        }

        $response = $this->client->post($url, [
            'body' => array_merge(['cmd' => '_notify-validate'], $vars)
        ]);

        $verified = ((string)$response->getBody()) == 'VERIFIED';
        if (!$verified) {
            throw new Exceptions\NotificationVerificationException($response, $notification);
        }
        return $verified;
    }
}
