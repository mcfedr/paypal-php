<?php

namespace Mcfedr\Paypal\Notifications;

class MasspayNotification extends Notification
{

    public function __construct($vars, $i)
    {
        parent::__construct($vars);
        $this->type = static::MASSPAY;

        if (isset($vars["masspay_txn_id_$i"])) {
            $this->transactionId = $vars["masspay_txn_id_$i"];
        }

        if (isset($vars["unique_id_$i"])) {
            $this->invoiceId = $vars["unique_id_$i"];
        }

        if (isset($vars["mc_gross_$i"])) {
            $this->total = $vars["mc_gross_$i"];
            $this->amount = $this->total;
        }

        if (isset($vars["mc_fee_$i"])) {
            $this->fee = $vars["mc_fee_$i"];
        }

        if (isset($vars["status_$i"])) {
            $this->status = $vars["status_$i"];
        }

        if (isset($vars["mc_currency_$i"])) {
            $this->currency = $vars["mc_currency_$i"];
        }

        if (isset($vars['payer_email'])) {
            $this->business = $vars['payer_email'];
        }
    }

}
