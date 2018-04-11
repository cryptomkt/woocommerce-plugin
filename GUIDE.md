# Using the cryptomarket plugin for WooCommerce

## Prerequisites

* Last Version Tested: Wordpress 4.5.2 WooCommerce 2.5.5

You must have a cryptomarket merchant account to use this plugin.  It's free to [sign-up for a cryptomarket merchant account](https://cryptomarket.com/start).


## Server Requirements

* [Wordpress](https://wordpress.org/about/requirements/) >= 4.3.1 (Older versions will work, but we do not test against those)
* [WooCommerce](http://docs.woothemes.com/document/server-requirements/) >= 2.4.10
* [GMP](http://php.net/manual/en/book.gmp.php) or [BCMath](http://php.net/manual/en/book.bc.php) You may have to install GMP as most servers do not come with it, but generally BCMath is already included.
* [mcrypt](http://us2.php.net/mcrypt)
* [OpenSSL](http://us2.php.net/openssl) Must be compiled with PHP
* [PHP5 Curl](http://php.net/manual/en/curl.installation.php) Must be compiled with PHP
* PHP >= 5.5 (we tested this on 5.5)
* Be sure to restart apache after the installation:

```bash
sudo apachectl restart
```

## Installation

### When Upgrading From Version 1.x to 2.x

**Please Note:** Merchants who have a previous version of the WooCommerce cryptomarket Payment Gateway will need to remove it.
This can be done by going to the Wordpress's Adminstration Panels > Plugins.  Deactivate the old plugin, then delete it.

### When Installing From the Downloadable Archive

Visit the [Releases](https://github.com/cryptomarket/woocommerce-plugin/releases) page of this repository and download the latest version. Once this is done, you can just go to Wordpress's Adminstration Panels > Plugins > Add New > Upload Plugin, select the downloaded archive and click Install Now. After the plugin is installed, click on Activate.


**WARNING:** It is good practice to backup your database before installing plugins. Please make sure you create backups.


## Configuration

Configuration can be done using the Administrator section of Wordpress.
Once Logged in, you will find the configuration settings under **WooCommerce > Settings > Checkout > cryptomarket**.
Alternatively, you can also get to the configuration settings via Plugins and clicking the Settings link for this plugin.

![cryptomarket Settings](https://raw.githubusercontent.com/cryptomarket/woocommerce-plugin/master/docs/img/admin.png "cryptomarket Settings")

Here your will need to create a [pairing code](https://cryptomarket.com/api-tokens) using
your cryptomarket merchant account. Once you have a Pairing Code, put the code in the
Pairing Code field:
![Pairing Code field](https://raw.githubusercontent.com/cryptomarket/woocommerce-plugin/master/docs/img/pairingcode.png "Pairing Code field")

On success, you'll receive a token:
![cryptomarket Token](https://raw.githubusercontent.com/cryptomarket/woocommerce-plugin/master/docs/img/token.png "cryptomarket Token")

**NOTE:** Pairing Codes are only valid for a short period of time. If it expires
before you get to use it, you can always create a new one and pair with it.

**NOTE:** You will only need to do this once since each time you do this, the
extension will generate public and private keys that are used to identify you
when using the API.

You are also able to configure how cryptomarket's IPN (Instant Payment Notifications)
changes the order in your WooCommerce store.

![Invoice Settings](https://raw.githubusercontent.com/cryptomarket/woocommerce-plugin/master/docs/img/ordersettings.png "Invoice Settings")

Save your changes and you're good to go!

## Usage

Once enabled, your customers will be given the option to pay with Bitcoins. Once
they checkout they are redirected to a full screen cryptomarket invoice to pay for
the order.

As a merchant, the orders in your WooCommerce store can be treated as any other
order. You may need to adjust the Invoice Settings depending on your order
fulfillment.


## How to Get Optimal Performance From the Plugin

It is highly recommended you install the GMP extension for PHP to acheive maximum performance when using this plugin.

### Compile PHP with GMP

[http://php.net/manual/en/gmp.installation.php](http://php.net/manual/en/gmp.installation.php)

### Enable Extension

If the extension has been included with your PHP install, you only need to uncomment the line in the PHP ini configuration file.

**On Windows:**

```ini
; From
;extension=php_gmp.dll
; To
extension=php_gmp.dll
```

**For Ubuntu:**

```bash
$ sudo apt-get update
$ sudo apt-get install php5-gmp
$ sudo php5enmod gmp

# Restart your server
```

**For Other Linux Systems:**

```ini
; From
;extension=gmp.so
; To
extension=gmp.so

# Restart your server
```
