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
	        "mcfedr/paypal": "2.0.*"
	    }
	}

### Standard

The other choice is to checkout the code, and register it with your autoloader. If you need one [ClassLoader](https://github.com/symfony/ClassLoader) is the place to start.
