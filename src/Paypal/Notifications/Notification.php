<?php

namespace Paypal\Notifications;

/**
 * Describes a notification
 */
abstract class Notification {

    /**
     * Type of notification
     * 
     * @see REFUND
     * @see CART
     * @see MASSPAY
     * @see SUBSCRIPTION
     * @see ADAPTIVE
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
     * type for masspay notification
     */
    const MASSPAYS = 'masspays';

    /**
     * type for subscription notifications
     */
    const SUBSCRIPTION = 'subscription';
    
    const ADAPTIVE = 'adaptive';

    /**
     * Type of transaction (paypal)
     * @see TXT_CART
     * @see TXT_WEB_ACCEPT
     * @see TXT_MASSPAY
     * @see TXT_SUBSCRIPTION_START
     * @see TXT_SUBSCRIPTION_PAYMENT
     * @see TXT_SUBSCRIPTION_MODIFY
     * @see TXT_SUBSCRIPTION_FAILED
     * @see TXT_SUBSCRIPTION_EXPIRE
     * @see TXT_SUBSCRIPTION_CANCEL
     * @see TXT_ADAPTIVE_CREATE
     * @see TXT_ADAPTIVE_ADJUSTMENT
     * @var string 
     */
    public $transactionType;

    const TXT_CART = 'cart';
    const TXT_WEB_ACCEPT = 'web_accept';
    const TXT_MASSPAY = 'masspay';
    const TXT_SUBSCRIPTION_START = 'subscr_signup';
    const TXT_SUBSCRIPTION_PAYMENT = 'subscr_payment';
    const TXT_SUBSCRIPTION_MODIFY = 'subscr_modify';
    const TXT_SUBSCRIPTION_FAILED = 'subscr_failed';
    const TXT_SUBSCRIPTION_EXPIRE = 'subscr_eot';
    const TXT_SUBSCRIPTION_CANCEL = 'subscr_cancel';
    const TXT_ADAPTIVE_CREATE = 'Adaptive Payment PAY';
    const TXT_ADAPTIVE_ADJUSTMENT = 'Adjustment';

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
     * Currency paid in
     * @var string 
     */
    public $currency;

    /**
     * The paypal fee (you received {@link $amount}-{@link $fee})
     * @var double
     */
    public $fee;

    /**
     * Who was paid
     * @var string
     */
    public $business;

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
     * Time the payment was made
     * @var DateTime
     */
    public $date;

    /**
     * Has this notification been sent before
     * @var bool
     */
    public $resent;

    /**
     * Is this a sandbox notification
     * @var bool
     */
    public $sandbox;

    public function __construct($vars) {
        if (isset($vars['txn_type'])) {
            $this->transactionType = $vars['txn_type'];
        }

        if (isset($vars['txn_id'])) {
            $this->transactionId = $vars['txn_id'];
        }

        if (isset($vars['parent_txn_id'])) {
            $this->parentTransactionId = $vars['parent_txn_id'];
        }

        if (isset($vars['invoice'])) {
            $this->invoiceId = $vars['invoice'];
        }

        if (isset($vars['custom'])) {
            $this->custom = $vars['custom'];
        }

        if (isset($vars['payment_status'])) {
            $this->status = $vars['payment_status'];
        }

        if (isset($vars['pending_reason'])) {
            $this->pendingReason = $vars['pending_reason'];
        }

        if (isset($vars['payment_date'])) {
            $this->date = new \DateTime($vars['payment_date']);
        }

        $this->resent = isset($vars['resend']) && $vars['resend'] == 'true';
        $this->sandbox = isset($vars['test_ipn']) && $vars['test_ipn'] == 1;
    }

    /**
     * Check everything is as expected
     * 
     * @param \Paypal\Authenticaton $authentication
     * @param \Paypal\Settings $settings
     * @throws \Paypal\Exceptions\NotificationInvalidException
     * @return bool
     */
    public function isOK(\Paypal\Authentication $authentication, \Paypal\Settings $settings) {
        return $this->isBusinessCorrect($authentication) && $this->isCurrencyCorrect($settings);
    }

    /**
     * Check that the notification matches the expected business
     * 
     * @param \Paypal\Authenticaton $authentication
     * @throws \Paypal\Exceptions\NotificationBusinessInvalidException
     * @return bool
     */
    private function isBusinessCorrect(\Paypal\Authentication $authentication) {
        if ($this->business != $authentication->getEmail() || $this->sandbox != $authentication->isSandbox()) {
            throw new \Paypal\Exceptions\NotificationBusinessInvalidException($this);
        }
        return true;
    }

    /**
     * Check the correct currency was used
     * 
     * @param \Paypal\Settings $setting
     * @throws \Paypal\Exceptions\NotificationCurrencyInvalidException
     * @return bool
     */
    private function isCurrencyCorrect(\Paypal\Settings $settings) {
        if ($this->currency != $settings->currency) {
            throw new \Paypal\Exceptions\NotificationCurrencyInvalidException($this, $this->currency, $settings->currency);
        }
        return true;
    }

}
