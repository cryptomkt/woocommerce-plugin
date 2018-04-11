# Notice

This is a Community-supported project.

If you are interested in becoming a maintainer of this project, please contact us at integrations@cryptomarket.com. Developers at cryptomarket will attempt to work along the new maintainers to ensure the project remains viable for the foreseeable future.

WooCommerce CryptoMarket Payment Gateway
=====================

## Build Status

[![Build Status](https://travis-ci.org/cryptomarket/woocommerce-plugin.svg?branch=master)](https://travis-ci.org/cryptomarket/woocommerce-plugin)

## Brief Description

Add the ability to accept bitcoin in WooCommerce via cryptomarket.

## Detail Description

Bitcoin is a powerful new peer-to-peer platform for the next generation of financial technology. The decentralized nature of the Bitcoin network allows for a highly resilient value transfer infrastructure, and this allows merchants to gain greater profits.

This is because there are little to no fees for transferring Bitcoins from one person to another. Unlike other payment methods, Bitcoin payments cannot be reversed, so once you are paid you can ship! No waiting days for a payment to clear.


## Quick Start Guide

To get up and running with our plugin quickly, see the GUIDE here: https://github.com/cryptomarket/woocommerce-plugin/blob/master/GUIDE.md


## Development

### Setup

 * NodeJS & NPM
 * Grunt
 * Composer
 
Clone the repo:
```bash
$ git clone https://github.com/cryptomarket/woocommerce-plugin
$ cd woocommerce-plugin
```

Install the dependencies:
```bash
$ npm install
$ curl -sS https://getcomposer.org/installer | php
$ ./composer.phar install
```

### Build

Perform the [setup](#Setup), then:
```bash
$ ./node_modules/.bin/grunt build
# Outputs plugin at dist/woocommerce-plugin
# Outputs plugin archive at dist/woocommerce-plugin.zip
```

## Support

### cryptomarket Support

* Last Version Tested: Wordpress 4.8.1 WooCommerce 3.1.2
* [GitHub Issues](https://github.com/cryptomarket/woocommerce-plugin/issues)
  * Open an issue if you are having issues with this plugin.
* [Support](https://help.cryptomarket.com)
  * cryptomarket merchant support documentation

### WooCommerce Support

* [Homepage](http://www.woothemes.com/woocommerce/)
* [Documentation](http://docs.woothemes.com)
* [Support](https://support.woothemes.com)

## Troubleshooting

1. Ensure a valid SSL certificate is installed on your server. Also ensure your root CA cert is updated. If your CA cert is not current, you will see curl SSL verification errors.
2. Verify that your web server is not blocking POSTs from servers it may not recognize. Double check this on your firewall as well, if one is being used.
3. Check the version of this plugin against the official plugin repository to ensure you are using the latest version. Your issue might have been addressed in a newer version! See the [Releases](https://github.com/cryptomarket/woocommerce-plugin/releases) page for the latest.
4. If all else fails, enable debug logging in the plugin options and send the log along with an email describing your issue **in detail** to support@cryptomarket.com

**TIP**: When contacting support it will help us is you provide:

* WordPress and WooCommerce Version
* Other plugins you have installed
  * Some plugins do not play nice
* Configuration settings for the plugin (Most merchants take screen grabs)
* Any log files that will help
  * Web server error logs
* Screen grabs of error message if applicable.

## Contribute

Would you like to help with this project?  Great!  You don't have to be a developer, either.  If you've found a bug or have an idea for an improvement, please open an [issue](https://github.com/cryptomarket/woocommerce-plugin/issues) and tell us about it.

If you *are* a developer wanting contribute an enhancement, bugfix or other patch to this project, please fork this repository and submit a pull request detailing your changes.  We review all PRs!

This open source project is released under the [MIT license](http://opensource.org/licenses/MIT) which means if you would like to use this project's code in your own project you are free to do so.  Speaking of, if you have used our code in a cool new project we would like to hear about it!  Please send us an email or post a new thread on [cryptomarket Labs](https://labs.cryptomarket.com).

## License

Please refer to the [LICENSE](https://github.com/cryptomarket/woocommerce-plugin/blob/master/LICENSE) file that came with this project.
