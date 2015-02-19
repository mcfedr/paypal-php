<?php
require 'common.php';
$query = "transaction_subject=Easy+Publishing&payment_date=08%3A15%3A40+Apr+22%2C+2013+PDT&txn_type=subscr_payment&subscr_id=I-CFAXB3H2DVRM&last_name=Test&residence_country=GB&item_name=Easy+Publishing&payment_gross=249.00&mc_currency=USD&business=caroline-facilitator%40yevpak.com&payment_type=instant&protection_eligibility=Ineligible&verify_sign=AaQ8o.zvozAVAJq4.g7xfH7gb2GVAa8.pddg.YogNZxdopbZbj7r8V8Z&payer_status=verified&test_ipn=1&payer_email=paypaltest%40ekreative.com&txn_id=5J621336AL745291X&receiver_email=caroline-facilitator%40yevpak.com&first_name=Ekreative&invoice=1&payer_id=MSGXU76U2EQVU&receiver_id=6PTX36VWTPWVY&item_number=2&payment_status=Completed&payment_fee=10.01&mc_fee=10.01&mc_gross=249.00&custom=1&charset=windows-1252&notify_version=3.7&ipn_track_id=26c88083a37ec";
parse_str($query, $arr);

$paypal = paypal();

try {
    $notif = $paypal->handleNotification($arr);
    print_r($notif);
} catch (Exception $e) {
    print_r($e);
}
