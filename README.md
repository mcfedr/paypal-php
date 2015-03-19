# Paypal

A simple SDK for dealing with the Paypal API from PHP.

[![Latest Stable Version](https://poser.pugx.org/mcfedr/paypal/v/stable.png)](https://packagist.org/packages/mcfedr/paypal)
[![License](https://poser.pugx.org/mcfedr/paypal/license.png)](https://packagist.org/packages/mcfedr/paypal)
[![Build Status](https://travis-ci.org/mcfedr/paypal-php.svg?branch=master)](https://travis-ci.org/mcfedr/paypal-php)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/ebc0b61e-35c8-4d26-b28f-a72f524b87de/mini.png)](https://insight.sensiolabs.com/projects/ebc0b61e-35c8-4d26-b28f-a72f524b87de)

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

	composer require mcfedr/paypal

### Standard

The other choice is to checkout the code, and register it with your autoloader. If you need one [ClassLoader](https://github.com/symfony/ClassLoader) is the place to start.

## Contributing

Please feel free to post issues, or even better pull requests right here on github.

It would be great to flesh out the functionality to cover the other parts of paypal api.

Note that recently paypal have started providing much more themselves - [PayPal on GitHub](https://github.com/paypal).
Although a good IPN lib is still missing.

## License

The library is covered by the MIT license. There is a copy in the repo.
