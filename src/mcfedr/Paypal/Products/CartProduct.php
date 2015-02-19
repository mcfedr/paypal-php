<?php

namespace Mcfedr\Paypal\Products;

/**
 * Describes a product to be sold
 * Some vars effect selling
 * Some are set when receiving a notification
 */
class CartProduct extends Product
{

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
     * cost of shipping further items
     * @var double
     */
    public $shipping2;

    /**
     * weight of this item if your account is setup to use weight base shipping
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
    public $shippingTotal;

    /**
     * Get a product from $vars
     *
     * @param array $vars
     * @param string $number use when more than one product eg '1', '2'
     */
    public function __construct($vars = null, $number = '')
    {
        parent::__construct($vars, $number);

        if (!is_null($vars)) {
            if (isset($vars["quantity$number"])) {
                $this->quantity = $vars["quantity$number"];
            }
            if (isset($vars["mc_shipping$number"])) {
                $this->shippingTotal = $vars["mc_shipping$number"];
            }
            if (isset($vars["mc_handling$number"])) {
                $this->handling = $vars["mc_handling$number"];
            }
            if (isset($vars["mc_gross_$number"])) {
                $this->total = $vars["mc_gross_$number"];
                $this->amount = $this->total - (empty($this->shippingTotal) ? 0 : $this->shippingTotal) - (empty($this->handling) ? 0 : $this->handling);
            } else {
                if (isset($vars["mc_gross$number"])) {
                    $this->total = $vars["mc_gross$number"];
                    $this->amount = $this->total - (empty($this->shippingTotal) ? 0 : $this->shippingTotal) - (empty($this->handling) ? 0 : $this->handling);
                }
            }
        }
    }

    /**
     * Sets up the array with paypal vars for $product
     *
     * @param array $params
     * @param string $suffix used when more than one product is set eg "_1", "_2"
     */
    public function setParams(&$params, $suffix = '')
    {
        parent::setParams($params, $suffix);

        $params["amount$suffix"] = $this->amount;
        $params["quantity$suffix"] = empty($this->quantity) ? 1 : $this->quantity;
        if (!empty($this->discount)) {
            $params["discount_amount$suffix"] = $this->discount;
        }
        if (!empty($this->tax)) {
            $params["tax$suffix"] = $this->tax;
        }
        if (!empty($this->shipping)) {
            $params["shipping$suffix"] = $this->shipping;
            if (!empty($this->shipping2)) {
                $params["shipping2$suffix"] = $this->shipping2;
            } else {
                $params["shipping2$suffix"] = $this->shipping;
            }
        }
        if (!empty($this->handling)) {
            $params["handling$suffix"] = $this->handling;
        }
        if (!empty($this->weight)) {
            $params["weight$suffix"] = $this->weight;
        }
    }

}
