<?php

namespace mcfedr\Paypal\Notifications;

use mcfedr\Paypal\Authentication;
use mcfedr\Paypal\Exceptions\NotificationBusinessInvalidException;
use mcfedr\Paypal\Settings;

class AdaptivePaymentNotification extends PaymentNotification {

    /**
     * Status of Payment
     * @var string
     * The status of the payment. Possible values are:
     * CREATED – The payment request was received; funds will be transferred once the payment is approved
     * COMPLETED – The payment was successful
     * INCOMPLETE – Some transfers succeeded and some failed for a parallel payment or, for a delayed chained payment, secondary receivers have not been paid
     * ERROR – The payment failed and all attempted transfers failed or all completed transfers were successfully reversed
     * REVERSALERROR – One or more transfers failed when attempting to reverse a payment
     * PROCESSING – The payment is in progress
     * PENDING – The payment is awaiting processing
     */
    public $status;

    /**
     * Whether the Pay API is used with or without the SetPaymentOptions and ExecutePayment API operations. Possible values are:
     * PAY – If you are not using the SetPaymentOptions and ExecutePayment API operations
     * CREATE – If you are using the SetPaymentOptions and ExecutePayment API operations
     * @var string 
     */
    public $actionType;

    /**
     * Whether the payment request specified to reverse parallel payments if an error occurs. Possible values are:
     * true – Each parallel payment is reversed if an error occurs
     * false – Only incomplete payments are reversed (default)
     * @var bool
     */
    public $reverseAllParallelPaymentsOnError;

    /**
     * The pay key that identifies this payment.
     * This is a token that is assigned by the Pay API after a PayRequest message
     * is received and can be used in other Adaptive Payments APIs as well
     * as the cancelURL and returnURL to identify this payment.
     * The pay key is valid for 3 hours.
     * @var string
     */
    public $payKey;

    /**
     * The payer of PayPal fees. Possible values are:
     * SENDER – Sender pays all fees (for personal, implicit simple/parallel payments; do not use for chained or unilateral payments)
     * PRIMARYRECEIVER – Primary receiver pays all fees (chained payments only)
     * EACHRECEIVER – Each receiver pays their own fee (default, personal and unilateral payments)
     * SECONDARYONLY – Secondary receivers pay all fees (use only for chained payments with one secondary receiver)
     * @var string
     */
    public $feesPayer;

    /**
     * The preapproval key returned after a PreapprovalRequest,
     * or the preapproval key that identifies the preapproval key sent with a PayRequest.
     * @var string
     */
    public $preapprovalKey;

    /**
     * Whether this transaction is a chargeback, partial, or reversal. Possible values are:
     * Chargeback Settlement – Transaction is a chargeback
     * Admin reversal – Transaction was reversed by PayPal administrators
     * Refund – Transaction was partially or fully refunded
     * @var string
     */
    public $reasonCode;

    /**
     * The tracking ID that was specified for this payment in the PaymentDetailsRequest message.
     * @var string
     */
    public $trackingId;

    public function __construct($vars) {
        parent::__construct($vars);
        $this->type = static::ADAPTIVE;

        if (isset($vars['transaction_type'])) {
            $this->transactionType = $vars['transaction_type'];
        }

        if (isset($vars['status'])) {
            $this->status = $vars['status'];
        }

        if (isset($vars['payment_request_date'])) {
            $this->date = new \DateTime($vars['payment_request_date']);
        }

        if (isset($vars['action_type'])) {
            $this->actionType = $vars['action_type'];
        }

        if (isset($vars['pay_key'])) {
            $this->payKey = $vars['pay_key'];
        }

        if (isset($vars['fees_payer'])) {
            $this->feesPayer = $vars['fees_payer'];
        }

        if (isset($vars['trackingId'])) {
            $this->invoiceId = $vars['trackingId'];
        }

        if (isset($vars['preapproval_key'])) {
            $this->preapprovalKey = $vars['preapproval_key'];
        }

        if (isset($vars['reason_code'])) {
            $this->reasonCode = $vars['reason_code'];
        }

        $this->reverseAllParallelPaymentsOnError = isset($vars['reverse_all_parallel_payments_on_error']) && $vars['reverse_all_parallel_payments_on_error'] == 'true';
    }

    /**
     * Check that the notification matches the expected business
     *
     * @param Authentication $authentication
     * @throws NotificationBusinessInvalidException
     * @return bool
     */
    protected function isBusinessCorrect(Authentication $authentication) {
        if ($this->sandbox != $authentication->isSandbox()) {
            throw new NotificationBusinessInvalidException($this);
        }
        return true;
    }

    /**
     * Check the correct currency was used
     *
     * @param Settings $settings
     * @return bool
     */
    protected function isCurrencyCorrect(Settings $settings) {
        return true;
    }
}
