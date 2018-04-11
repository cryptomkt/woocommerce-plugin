# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [2.2.14] - 2018-01-15
### Fixed
- (fixed via PHP package update) Price must be formatted as a float (#78)
- get_billing_email() Method creates fatal Error (#83)

## [2.2.13] - 2018-01-09
### Fixed
- wrong function call resulting in undefined wc_reduce_stock_levels() (#84)
- syntax error in class-wc-gateway-cryptomarket.php (#80)
- Price must be formatted as a float (#78)

### Added
- Redirect page displays 'payment successful' even for unpaid invoices (#81)

## [2.2.12] - 2017-09-29
### Fixed
- Removed non-working option to disable cryptomarket from the cryptomarket plugin config page
- Populate buyer email when creating cryptomarket invoice
- WC v3 compatibility fixes
- Change Mcrypt to OpenSSL (#77)

### Added
- Improve logging around updating order states
- Present error when mcrypt is not loaded

## [2.2.11-beta] - 2016-06-14
### Fixed
- order_total with certain filters

## [2.2.10] - 2016-06-6
### Fixed
- Use order numbering system for IPN callbacks

## [2.2.9] - 2015-12-04
### Fixed
- Fixed notification URL initialization

## [2.2.8] - 2015-11-19
### Fixed
- Fixed missing API field in config page

## [2.2.7] - 2015-05-28
### Fixed
- Security issue with ajax calls

## [2.2.6] - 2015-04-20
### Added
- New order status setting which also fixes issues with new orders being set to On-Hold and triggering emails

## [2.2.5] - 2015-04-02
### Fixed
- Bundled cryptomarket PHP Client for releases now includes entire client

## [2.2.4] - 2015-03-09
### Added
- Curl requirement check during activation
- Notification and Redirect URL settings for advanced users

### Fixed
- Order States now save correctly to the database

## [2.2.3] - 2015-02-24
### Fixed
- Requirements check doesn't lock up WordPress when WooCommerce is upgraded

## [2.2.2] - 2015-01-13
### Fixed
- Checkout error message when invoice can't be generated
- Admin error message when pairing with cryptomarket fails

## [2.2.1] - 2014-12-10
### Fixed
- Token pairing label sanitization which caused issues when accented characters or symbols were used

## [2.2.0] - 2014-12-05
### Changed
- More robust debug logging

### Fixed
- PHP 5.4 related issues (array literals, api credentials' serialization)

## [2.1.0] - 2014-11-28
### Changed
- Uses newer cryptomarket Library that no longer solely requires GMP, but can use BCMath as an alternative

## [2.0.2] - 2014-11-20
### Fixed
- Payment method description/message display on checkout

## [2.0.1] - 2014-11-19
### Changed
- Plugin activation fails on presence of old plugin instead of attempting to delete old plugin and also detect GMP requirement.

## 2.0.0 - 2014-11-18
### Changed
- Implements cryptomarket's new cryptographically secure authentication.

[unreleased]: https://github.com/cryptomarket/woocommerce-plugin/compare/v2.2.7...HEAD
[2.2.7]: https://github.com/cryptomarket/woocommerce-plugin/compare/v2.2.6...v2.2.7
[2.2.6]: https://github.com/cryptomarket/woocommerce-plugin/compare/v2.2.5...v2.2.6
[2.2.5]: https://github.com/cryptomarket/woocommerce-plugin/compare/v2.2.4...v2.2.5
[2.2.4]: https://github.com/cryptomarket/woocommerce-plugin/compare/v2.2.3...v2.2.4
[2.2.3]: https://github.com/cryptomarket/woocommerce-plugin/compare/v2.2.2...v2.2.3
[2.2.2]: https://github.com/cryptomarket/woocommerce-plugin/compare/v2.2.1...v2.2.2
[2.2.1]: https://github.com/cryptomarket/woocommerce-plugin/compare/v2.2.0...v2.2.1
[2.2.0]: https://github.com/cryptomarket/woocommerce-plugin/compare/v2.1.0...v2.2.0
[2.1.0]: https://github.com/cryptomarket/woocommerce-plugin/compare/v2.0.2...v2.1.0
[2.0.2]: https://github.com/cryptomarket/woocommerce-plugin/compare/v2.0.1...v2.0.2
[2.0.1]: https://github.com/cryptomarket/woocommerce-plugin/compare/v2.0.0...v2.0.1
