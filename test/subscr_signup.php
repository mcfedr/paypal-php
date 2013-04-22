<?php
require 'common.php';
$query = "txn_type=subscr_signup&subscr_id=I-CFAXB3H2DVRM&last_name=Test&residence_country=GB&mc_currency=USD&item_name=Easy+Publishing&business=caroline-facilitator%40yevpak.com&amount3=249.00&recurring=1&verify_sign=AEtPq49P.OXLGAXHk-9dpo6PaGcGA0GaAy8inKQ0QGYhTzEAs5pO8yRj&payer_status=verified&test_ipn=1&payer_email=paypaltest%40ekreative.com&first_name=Ekreative&receiver_email=caroline-facilitator%40yevpak.com&payer_id=MSGXU76U2EQVU&invoice=1&reattempt=1&item_number=2&subscr_date=08%3A15%3A39+Apr+22%2C+2013+PDT&custom=1&charset=windows-1252&notify_version=3.7&period3=1+M&mc_amount3=249.00&ipn_track_id=26c88083a37ec";
parse_str($query, $arr);

$paypal = paypal();

try {
	$notif = $paypal->handleNotification($arr);
	print_r($notif);
}
catch (Exception $e) {
	print_r($e);
}
