<?php

namespace Mcfedr\Paypal;

/**
 * Describes the buyer of your product, this info is received in notifications
 */
class Buyer
{

    /**
     * Unique id for this buyer
     * @var string
     */
    public $id;

    /**
     * First name
     * @var string
     */
    public $firstName;

    /**
     * Last name
     * @var string
     */
    public $lastName;

    /**
     * Email
     * @var string
     */
    public $email;

    /**
     * business name if has one
     * @var string
     */
    public $business;

    /**
     * contact phone
     * @var string
     */
    public $phone;

    /**
     * status, ie verified or not
     * @var string
     */
    public $status;

    /**
     * Code of country of residence
     * @var string
     */
    public $residenceCountry;

    /**
     * Country
     * @var string
     */
    public $addressCountry;

    /**
     * Country code ie gb or fr etc
     * @var string
     */
    public $addressCountryCode;

    /**
     * Zip code or similar
     * @var string
     */
    public $addressZip;

    /**
     * State
     * @var string
     */
    public $addressState;

    /**
     * City
     * @var string
     */
    public $addressCity;

    /**
     * Street
     * @var string
     */
    public $addressStreet;

    /**
     * Name to be used with address
     * @var string
     */
    public $addressName;

    /**
     * status of address, ie verifed or not
     * @var string
     */
    public $addressStatus;

    /**
     * Extract buyer info from the vars from notifications
     * @param array $vars
     */
    public function __construct($vars)
    {
        if (isset($vars['payer_id'])) {
            $this->id = $vars['payer_id'];
        }

        if (isset($vars['first_name'])) {
            $this->firstName = $vars['first_name'];
        }

        if (isset($vars['last_name'])) {
            $this->lastName = $vars['last_name'];
        }

        if (isset($vars['payer_email'])) {
            $this->email = $vars['payer_email'];
        } else {
            if (isset($vars['sender_email'])) {
                $this->email = $vars['sender_email'];
            }
        }

        if (isset($vars['payer_business_name'])) {
            $this->business = $vars['payer_business_name'];
        }

        if (isset($vars['contact_phone'])) {
            $this->phone = $vars['contact_phone'];
        }

        if (isset($vars['payer_status'])) {
            $this->status = $vars['payer_status'];
        }

        if (isset($vars['residence_country'])) {
            $this->residenceCountry = $vars['residence_country'];
        }

        if (isset($vars['address_country'])) {
            $this->addressCountry = $vars['address_country'];
        }

        if (isset($vars['address_country_code'])) {
            $this->addressCountryCode = $vars['address_country_code'];
        }

        if (isset($vars['address_zip'])) {
            $this->addressZip = $vars['address_zip'];
        }

        if (isset($vars['address_state'])) {
            $this->addressState = $vars['address_state'];
        }

        if (isset($vars['address_city'])) {
            $this->addressCity = $vars['address_city'];
        }

        if (isset($vars['address_street'])) {
            $this->addressStreet = $vars['address_street'];
        }

        if (isset($vars['address_name'])) {
            $this->addressName = $vars['address_name'];
        }

        if (isset($vars['address_status'])) {
            $this->addressStatus = $vars['address_status'];
        }
    }

    /**
     * Set params in button for buyer
     *
     * @param array $params
     */
    public function setParams(&$params)
    {
        if (!empty($this->email)) {
            $params['email'] = $this->email;
        }
        if (!empty($this->firstName)) {
            $params['first_name'] = $this->firstName;
        }
        if (!empty($this->lastName)) {
            $params['last_name'] = $this->lastName;
        }
        //TODO: address fields
    }

}
