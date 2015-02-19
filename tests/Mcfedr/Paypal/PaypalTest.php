<?php

namespace Mcfedr\Paypal;

class PaypalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Paypal
     */
    private $paypal;

    public function setUp()
    {
        $auth = new Authentication('caroline-facilitator@yevpak.com', 'caroline-facilitator_api1.yevpak.com',
            '1366189350', 'AFcWxV21C7fd0v3bYYYRCpSSRl31AsTaxb3dYGFYKOc-FyFf7q8jzNmL', true);
        $settings = new Settings();
        $settings->currency = 'USD';
        $this->paypal = new Paypal($auth, $settings);
    }

    /**
     * @dataProvider subscrSignupProvider
     * @param $_post
     */
    public function testSubscrSignupNotification($_post)
    {
        $notification = $this->paypal->handleNotification($_post);
        $this->assertInstanceOf('Mcfedr\Paypal\Notifications\SubscriptionNotification', $notification);
    }

    /**
     * This is a sample post from Paypal IPN
     *
     * @return array
     */
    public function subscrSignupProvider()
    {
        return [
            [
                [
                    'txn_type' => 'subscr_signup',
                    'subscr_id' => 'I-CFAXB3H2DVRM',
                    'last_name' => 'Test',
                    'residence_country' => 'GB',
                    'mc_currency' => 'USD',
                    'item_name' => 'Easy Publishing',
                    'business' => 'caroline-facilitator@yevpak.com',
                    'amount3' => '249.00',
                    'recurring' => '1',
                    'verify_sign' => 'AEtPq49P.OXLGAXHk-9dpo6PaGcGA0GaAy8inKQ0QGYhTzEAs5pO8yRj',
                    'payer_status' => 'verified',
                    'test_ipn' => '1',
                    'payer_email' => 'paypaltest@ekreative.com',
                    'first_name' => 'Ekreative',
                    'receiver_email' => 'caroline-facilitator@yevpak.com',
                    'payer_id' => 'MSGXU76U2EQVU',
                    'invoice' => '1',
                    'reattempt' => '1',
                    'item_number' => '2',
                    'subscr_date' => '08:15:39 Apr 22, 2013 PDT',
                    'custom' => '1',
                    'charset' => 'windows-1252',
                    'notify_version' => '3.7',
                    'period3' => '1 M',
                    'mc_amount3' => '249.00',
                    'ipn_track_id' => '26c88083a37ec',
                ]
            ]
        ];
    }

    /**
     * @dataProvider subscrPaymentProvider
     * @param array $_post
     */
    public function testSubscrPaymentNotification($_post)
    {
        $notification = $this->paypal->handleNotification($_post);
        $this->assertInstanceOf('Mcfedr\Paypal\Notifications\SubscriptionNotification', $notification);
    }

    /**
     * This is a sample post from Paypal IPN
     *
     * @return array
     */
    public function subscrPaymentProvider()
    {
        return [
            [
                [
                    'transaction_subject' => 'Easy Publishing',
                    'payment_date' => '08:15:40 Apr 22, 2013 PDT',
                    'txn_type' => 'subscr_payment',
                    'subscr_id' => 'I-CFAXB3H2DVRM',
                    'last_name' => 'Test',
                    'residence_country' => 'GB',
                    'item_name' => 'Easy Publishing',
                    'payment_gross' => '249.00',
                    'mc_currency' => 'USD',
                    'business' => 'caroline-facilitator@yevpak.com',
                    'payment_type' => 'instant',
                    'protection_eligibility' => 'Ineligible',
                    'verify_sign' => 'AaQ8o.zvozAVAJq4.g7xfH7gb2GVAa8.pddg.YogNZxdopbZbj7r8V8Z',
                    'payer_status' => 'verified',
                    'test_ipn' => '1',
                    'payer_email' => 'paypaltest@ekreative.com',
                    'txn_id' => '5J621336AL745291X',
                    'receiver_email' => 'caroline-facilitator@yevpak.com',
                    'first_name' => 'Ekreative',
                    'invoice' => '1',
                    'payer_id' => 'MSGXU76U2EQVU',
                    'receiver_id' => '6PTX36VWTPWVY',
                    'item_number' => '2',
                    'payment_status' => 'Completed',
                    'payment_fee' => '10.01',
                    'mc_fee' => '10.01',
                    'mc_gross' => '249.00',
                    'custom' => '1',
                    'charset' => 'windows-1252',
                    'notify_version' => '3.7',
                    'ipn_track_id' => '26c88083a37ec',
                ]
            ]
        ];
    }
}
