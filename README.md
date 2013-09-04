# Paypal

A simple SDK for dealing with the Paypal API from PHP.

## Features

* Generate button code
  * Single product
  * Multi-product (cart)
  * Subscriptions
* Handle Instant Notifcations
  * Handles verification with paypal
  * All types of payment notifcations
  * Subscription related notifcations
* Mass Payments
  * Instantly send money to other paypal users
* Modern PHP5 features
  * Namespaced
  * Autoloading
  * Exceptions
  
All of this with a consistent and simple API.

## Docs

Check the [current docs](http://mcfedr.github.io/paypal-php/) on the otherside.

Alternatively just run [`apigen`](http://apigen.org/) once you have cloned the repo

## Install

### Composer

[Composer](http://getcomposer.org/) is the best way to get started with an new project. This package is not far away… [Paypal at Packagist](https://packagist.org/packages/mcfedr/paypal)

	{
	    "require": {
	        "mcfedr/paypal": "~2.1.2"
	    }
	}

### Standard

The other choice is to checkout the code, and register it with your autoloader. If you need one [ClassLoader](https://github.com/symfony/ClassLoader) is the place to start.

## Contributing

Please feel free to post issues, or even better pull requests right here on github.

It would be great to flesh out the functionality to cover the other parts of paypal api.

Note that recently paypal have started providing much more themselves - [PayPal on GitHub](https://github.com/paypal).
Although a good IPN lib is still missing.

## License

The library is covered by the GPL v3 or higher. There is a copy in the repo.
