<?php

namespace Mcfedr\Paypal\Products;

/**
 * Describes a product to be sold
 * Some vars effect selling
 * Some are set when receiving a notification
 */
abstract class Product {

    /**
     * unique id of this product
     * @var int
     */
    public $id;

    /**
     * name of product
     * @var string
     */
    public $name;

    /**
     * Cost per item
     * @var double
     */
    public $amount;

    /**
     * total fee paid to paypal for these items
     * Set when received by notification
     * (ie you received {@link $total}-{@link $fee})
     * @var double
     */
    public $fee;

    /**
     * Get a product from $vars
     *
     * @param array $vars
     * @param string $number use when more than one product eg '1', '2'
     */
    public function __construct($vars = null, $number = '') {
        if (!is_null($vars)) {
            if (isset($vars["item_number$number"])) {
                $this->id = $vars["item_number$number"];
            }
            if (isset($vars["item_name$number"])) {
                $this->name = $vars["item_name$number"];
            }
            if (isset($vars["mc_fee_$number"])) {
                $this->fee = $vars["mc_fee_$number"];
            }
            else if (isset($vars["mc_fee$number"])) {
                $this->fee = $vars["mc_fee$number"];
            }
        }
    }

    /**
     * Sets up the array with paypal vars for $product
     *
     * @param array $params
     * @param string $suffix
     */
    public function setParams(&$params, $suffix = '') {
        if (!empty($this->id)) {
            $params["item_number$suffix"] = $this->id;
        }
        $params["item_name$suffix"] = substr($this->name, 0, 127);
    }

}
