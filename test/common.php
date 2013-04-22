<?php

require 'UniversalClassLoader.php';
$loader = new Symfony\Component\ClassLoader\UniversalClassLoader();
$loader->registerNamespaces(array('Paypal' => __DIR__ . '/../src'));
$loader->register();

function paypal() {
	//Create the authentication
	$auth = new Paypal\Authentication('caroline-facilitator@yevpak.com', 'caroline-facilitator_api1.yevpak.com', '1366189350', 'AFcWxV21C7fd0v3bYYYRCpSSRl31AsTaxb3dYGFYKOc-FyFf7q8jzNmL', true);
	$settings = new Paypal\Settings();
	$settings->currency = 'USD';
	//Create the paypal object
	$paypal = new Paypal\Paypal($auth, $settings);
	return $paypal;
}
