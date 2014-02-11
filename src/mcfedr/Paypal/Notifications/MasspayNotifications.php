<?php

namespace mcfedr\Paypal\Notifications;

use mcfedr\Paypal\Authentication;
use mcfedr\Paypal\Settings;

class MasspayNotifications extends Notification {

    /**
     * Sub notifications, used by masspay
     * @var MasspayNotification[]
     */
    public $notifications;

    public function __construct($vars) {
        parent::__construct($vars);
        $this->type = static::MASSPAYS;

        $this->notifications = array();
        for ($i = 1; isset($vars["status_$i"]); $i++) {
            $n = new MasspayNotification($vars, $i);
            $this->notifications[] = $n;
        }

        $this->amount = 0;
        $this->fee = 0;
        if (count($this->notifications)) {
            foreach ($this->notifications as $notification) {
                $this->amount += $notification->amount;
                $this->fee += $notification->fee;
            }
            $this->currency = $this->notifications[0]->currency;
        }
    }

    /**
     * Check everything is as expected
     * 
     * @param Authentication $authentication
     * @param Settings $settings
     * @return bool
     */
    public function isOK(Authentication $authentication, Settings $settings) {
        if (empty($this->notifications)) {
            return false;
        }
        foreach ($this->notifications as $notification) {
            if (!$notification->isOK($authentication, $settings)) {
                return false;
            }
        }
        return true;
    }

}
