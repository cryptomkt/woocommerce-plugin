
=== cryptomarket-for-woocommerce ===
Contributors: cryptomarket
Tags: bitcoin, payments, cryptomarket, cryptocurrency, payment
Requires at least: 4.3.1
Tested up to: 4.9.2
Requires PHP: 5.5
Stable tag: 2.2.14
License: MIT License (MIT)
License URI: https://opensource.org/licenses/MIT
 
cryptomarket allows you to accept bitcoin payments on your WooCommerce store.
 
== Description ==

Use cryptomarket's plugin to accept Bitcoin payments from customers anywhere on earth.

Key features:

* Support all bitcoin wallets that support payment protocol
* Price in your local currency, let customers pay with bitcoin
* Have an overview of all your bitcoin payments in your cryptomarket merchant dashboard at https://cryptomarket.com
* Refund your customers in bitcoin in your cryptomarket merchant dashboard at https://cryptomarket.com
 
= Installation =
This plugin requires Woocommerce. Please make sure you have Woocommerce installed.

1. Get started by signing up for a [cryptomarket merchant account.](https://cryptomarket.com/dashboard/signup)
1. Download the latest version of the cryptomarket plugin from the [Wordpress site.](https://downloads.wordpress.org/plugin/cryptomarket-for-woocommerce.2.2.14.zip)
1. Install the latest version of the cryptomarket plugin for Woocommerce:
	* Navigate to your WordPress Admin Panel and select Plugins > Add New > Upload Plugin.
	* Select the downloaded plugin and click "Install Now".
	* Select "Activate Plugin" to complete installation. 

= Connecting cryptomarket and Woocommerce =
After you have installed the cryptomarket plugin, you can configure the plugin:

1. Create a cryptomarket pairing code in your cryptomarket merchant dashboard:
	* Login to your [cryptomarket merchant account](https://cryptomarket.com/dashboard/login/) and select Payment Tools -> Manage API Tokens -> Add New Token -> Add Token
	* Copy the 7 character pairing code
2. Log in to your WordPress admin panel and select "Plugins" -> "Settings" link for the cryptomarket plugin.
	* Paste the 7 character pairing code into the "Pairing Code" field in your cryptomarket plugin and click "Find"
	* Click "Save changes" at the bottom

Pairing codes need to be used once and are only valid for 24 hours. If a code expires before you get to use it, you can always create a new one and pair with it.

Nice work! Your customers will now be able to check out with bitcoin on your WordPress site.

== Frequently Asked Questions ==

= How do I pay a cryptomarket invoice? =
You can pay a cryptomarket invoice with a Bitcoin wallet. You can either scan the QR code or copy/paste the payment link in your Bitcoin wallet.

More information about paying a cryptomarket invoice can be found [here.](https://support.cryptomarket.com/hc/en-us/articles/203281456-How-do-I-pay-a-cryptomarket-invoice-)

= Does cryptomarket have a test environment? =
cryptomarket allows you to create a test merchant account and a testnet Bitcoin wallet.

More information about the test environment can be found [here.](https://cryptomarket.com/docs/testing)

= The cryptomarket plugin does not work =
If cryptomarket invoices are not created, please check the following:

* The minimum invoice amount is USD 5. Please make sure you are trying to create a cryptomarket invoice for USD 5 or more (or your currency equivalent).
* Please make sure your cryptomarket merchant account is enabled for your transaction amounts. In your [cryptomarket merchant account](https://cryptomarket.com/dashboard/login/), go to Settings -> General -> Increase Processing Volume

= I need support from cryptomarket =
When contacting cryptomarket support, please describe your issue and attach screenshots and the cryptomarket logs.

cryptomarket logs can be retrieved in your Wordpress / Woocommerce environment:

* Enable logging in your cryptomarket plugin: Plugins -> Settings -> Debug Log -> Enable logging
* Download the logs from Plugins -> Logs

You can email your issue report to support@cryptomarket.com


== Changelog ==
= 2.2.14 =
* (fixed via PHP package update) Price must be formatted as a float (#78)
* Fixed WC 2.5 compatibility, with get_billing_email() error (#83)

= 2.2.13 = 
* Fixed wrong function call resulting in undefined wc_reduce_stock_levels() (#84)
* Fixed syntax error in class-wc-gateway-cryptomarket.php (#80)
* Fixed price must be formatted as a float (#78)
* Added redirect page, displaying 'payment successful' even for unpaid invoices (#81)

= 2.2.12 =
* Removed non-working option to disable cryptomarket from the cryptomarket plugin config page
* Populate buyer email when creating cryptomarket invoice
* WC v3 compatibility fixes
* Change Mcrypt to OpenSSL (#77)
* Improve logging around updating order states
