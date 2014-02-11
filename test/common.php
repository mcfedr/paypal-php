<?php

require '../vendor/autoload.php';

function paypal() {
	//Create the authentication
	$auth = new mcfedr\Paypal\Authentication('caroline-facilitator@yevpak.com', 'caroline-facilitator_api1.yevpak.com', '1366189350', 'AFcWxV21C7fd0v3bYYYRCpSSRl31AsTaxb3dYGFYKOc-FyFf7q8jzNmL', true);
	$settings = new mcfedr\Paypal\Settings();
	$settings->currency = 'USD';
	//Create the paypal object
	$paypal = new mcfedr\Paypal\Paypal($auth, $settings);
	return $paypal;
}
