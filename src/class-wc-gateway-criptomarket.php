<?php
/*
Plugin Name: cryptomarket for WooCommerce
Plugin URI:  https://cryptomarket.com
Description: Enable your WooCommerce store to accept Bitcoin with cryptomarket.
Author:      cryptomarket
Text Domain: cryptomarket
Author URI:  https://cryptomarket.com

Version:           2.2.14
License:           Copyright 2011-2018 cryptomarket Inc., MIT License
License URI:       https://github.com/cryptomkt/woocommerce-plugin/blob/master/LICENSE
GitHub Plugin URI: https://github.com/cryptomkt/woocommerce-plugin
 */

// Exit if accessed directly
if (false === defined('ABSPATH')) {
    exit;
}

//composer autoload
$loader = require __DIR__ . 'vendor/autoload.php';
$loader->add('Cryptomkt\\Exchange\\Client', __DIR__);
$loader->add('Cryptomkt\\Exchange\\Configuration as CMConfiguration', __DIR__);

// Ensures WooCommerce is loaded before initializing the cryptomarket plugin
add_action('plugins_loaded', 'woocommerce_cryptomarket_init', 0);
register_activation_hook(__FILE__, 'woocommerce_cryptomarket_activate');

function woocommerceCryptomarketInit() {
    class WcGatewayCryptomarket extends WC_Payment_Gateway {
        private $is_initialized = false;
        private $client;

        /**
         * Constructor for the gateway.
         */
        public function __construct() {
            // General
            $this->id = 'cryptomarket';
            $this->icon = plugin_dir_url(__FILE__) . 'assets/img/icon.png';
            $this->has_fields = false;
            $this->order_button_text = __('Proceed to cryptomarket', 'cryptomarket');
            $this->method_title = 'cryptomarket';
            $this->method_description = 'Cryptomarket allows you to accept ethereum payments on your WooCommerce store.';

            // Load the settings.
            $this->initFormFields();
            $this->init_settings();

            // Define user set variables
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->order_states = $this->get_option('order_states');
            $this->debug = 'yes' === $this->get_option('debug', 'no');

            // Define cryptomarket settings
            $this->payment_receiver = get_option('woocommerce_cryptomarket_payment_receiver');
            $this->api_key = get_option('woocommerce_cryptomarket_api_key');
            $this->api_secret = get_option('woocommerce_cryptomarket_api_secret');

            // Define debugging & informational settings
            $this->debug_php_version = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
            $this->debug_plugin_version = get_option('woocommerce_cryptomarket_version');

            $this->log('cryptomarket Woocommerce payment plugin object constructor called. Plugin is v' . $this->debug_plugin_version . ' and server is PHP v' . $this->debug_php_version);
            $this->log('[Info] $this->payment_receiver   = ' . $this->payment_receiver);
            $this->log('[Info] $this->api_key            = ' . $this->api_key);
            $this->log('[Info] $this->api_secret         = ' . $this->api_secret);

            $configuration = Cryptomkt\Exchange\Configuration::apiKey($this->api_key, $this->api_secret);
            $this->client = Cryptomkt\Exchange\Client::create($configuration);

            // Actions
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'save_order_states'));

            // Valid for use and IPN Callback
            if (false === $this->isValidForUse()) {
                $this->enabled = 'no';
                $this->log('[Info] The plugin is NOT valid for use!');
            } else {
                $this->enabled = 'yes';
                $this->log('[Info] The plugin is ok to use.');
                add_action('woocommerce_api_wc_gateway_cryptomarket', array($this, 'ipn_callback'));
            }

            $this->is_initialized = true;
        }

        public function __destruct() {
        }

        public function isValidForUse() {
            // Check that API credentials are set
            if (true === is_null($this->payment_receiver) ||
                true === is_null($this->api_key) ||
                true === is_null($this->api_secret)) {
                return false;
            }

            // Ensure the currency is supported by cryptomarket
            $currency = get_woocommerce_currency();

            try {
                $result = $this->client->getTicker(array('market' => 'ETH' . $currency->iso_code));
            } catch (Exception $e) {
                $this->log('Currency does not supported: ' . $currency);
                throw new \Exception('Currency does not supported: ' . $currency);
            }

            $this->log('[Info] Plugin is valid for use.');

            return true;
        }

        /**
         * Initialise Gateway Settings Form Fields
         */
        public function initFormFields() {
            $this->log('[Info] Entered init_form_fields()...');
            $log_file = 'cryptomarket-' . sanitize_file_name(wp_hash('cryptomarket')) . '-log';
            $logs_href = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-status&tab=logs&log_file=' . $log_file;

            $this->form_fields = array(
                'title' => array(
                    'title' => __('Title', 'cryptomarket'),
                    'type' => 'text',
                    'description' => __('Controls the name of this payment method as displayed to the customer during checkout.', 'cryptomarket'),
                    'default' => __('Ethereum', 'cryptomarket'),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('Customer Message', 'cryptomarket'),
                    'type' => 'textarea',
                    'description' => __('Message to explain how the customer will be paying for the purchase.', 'cryptomarket'),
                    'default' => 'You will be redirected to cryptomkt.com to complete your purchase.',
                    'desc_tip' => true,
                ),
                'api_token' => array(
                    'type' => 'api_token',
                ),
                'debug' => array(
                    'title' => __('Debug Log', 'cryptomarket'),
                    'type' => 'checkbox',
                    'label' => sprintf(__('Enable logging <a href="%s" class="button">View Logs</a>', 'cryptomarket'), $logs_href),
                    'default' => 'no',
                    'description' => sprintf(__('Log cryptomarket events, such as IPN requests, inside <code>%s</code>', 'cryptomarket'), wc_get_log_file_path('cryptomarket')),
                    'desc_tip' => true,
                ),
                'support_details' => array(
                    'title' => __('Plugin & Support Information', 'cryptomarket'),
                    'type' => 'title',
                    'description' => sprintf(__('This plugin version is %s and your PHP version is %s. If you need assistance, please contact support@cryptomkt.com.  Thank you for using cryptomarket!', 'cryptomarket'), get_option('woocommerce_cryptomarket_version'), PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION),
                ),
            );

            $this->log('[Info] Initialized form fields: ' . var_export($this->form_fields, true));
            $this->log('[Info] Leaving init_form_fields()...');
        }

        /**
         * Validate API Token
         */
        public function validateApiTokenField() {
            return '';
        }

        /**
         * Validate Support Details
         */
        public function validateSupportDetailsField() {
            return '';
        }

        /**
         * Validate Notification URL
         */
        public function validateUrlField($key) {
            $url = $this->get_option($key);

            if (isset($_POST[$this->plugin_id . $this->id . '_' . $key])) {
                if (filter_var($_POST[$this->plugin_id . $this->id . '_' . $key], FILTER_VALIDATE_URL) !== false) {
                    $url = $_POST[$this->plugin_id . $this->id . '_' . $key];
                } else {
                    $url = '';
                }
            }
            return $url;
        }

        /**
         * Output for the order received page.
         */
        public function thankyouPage($order_id) {
            $this->log('[Info] Entered thankyou_page with order_id =  ' . $order_id);

            // Intentionally blank.

            $this->log('[Info] Leaving thankyou_page with order_id =  ' . $order_id);
        }

        /**
         * Process the payment and return the result
         *
         * @param   int     $order_id
         * @return  array
         */
        public function processPayment($order_id) {
            $this->log('[Info] Entered process_payment() with order_id = ' . $order_id . '...');

            if (true === empty($order_id)) {
                $this->log('[Error] The cryptomarket payment plugin was called to process a payment but the order_id was missing.');
                throw new \Exception('The cryptomarket payment plugin was called to process a payment but the order_id was missing. Cannot continue!');
            }

            $order = wc_get_order($order_id);

            if (false === $order) {
                $this->log('[Error] The cryptomarket payment plugin was called to process a payment but could not retrieve the order details for order_id ' . $order_id);
                throw new \Exception('The cryptomarket payment plugin was called to process a payment but could not retrieve the order details for order_id ' . $order_id . '. Cannot continue!');
            }

            $this->log('[Info] The variable thanks_link = ' . $thanks_link . '...');

            // Setup the currency
            $currency_code = get_woocommerce_currency();

            // Setup the Invoice
            $invoice = new \cryptomarket\Invoice();

            if (false === isset($invoice) || true === empty($invoice)) {
                $this->log('    [Error] The cryptomarket payment plugin was called to process a payment but could not instantiate an Invoice object.');
                throw new \Exception('The cryptomarket payment plugin was called to process a payment but could not instantiate an Invoice object. Cannot continue!');
            } else {
                $this->log('    [Info] Invoice object created successfully...');
            }

            $order_total = $order->calculate_totals();

            // Reduce stock levels
            if (function_exists('wc_reduce_stock_levels')) {
                wc_reduce_stock_levels($order_id);
            } else {
                $order->reduce_order_stock();
            }

            // Remove cart
            WC()->cart->empty_cart();

            $this->log('    [Info] Leaving process_payment()...');

        }

        public function log($message) {
            if (true === isset($this->debug) && 'yes' == $this->debug) {
                if (false === isset($this->logger) || true === empty($this->logger)) {
                    $this->logger = new WC_Logger();
                }

                $this->logger->add('cryptomarket', $message);
            }
        }
    }

    /**
     * Add cryptomarket Payment Gateway to WooCommerce
     **/
    function wcAddCryptomarket($methods) {
        $methods[] = 'WC_Gateway_cryptomarket';

        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'wc_add_cryptomarket');

    /**
     * Add Settings link to the plugin entry in the plugins menu
     **/
    add_filter('plugin_action_links', 'cryptomarket_plugin_action_links', 10, 2);

    function cryptomarketPluginActionLinks($links, $file) {
        static $this_plugin;

        if (false === isset($this_plugin) || true === empty($this_plugin)) {
            $this_plugin = plugin_basename(__FILE__);
        }

        if ($file == $this_plugin) {
            $log_file = 'cryptomarket-' . sanitize_file_name(wp_hash('cryptomarket')) . '-log';
            $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=wc_gateway_cryptomarket">Settings</a>';
            $logs_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-status&tab=logs&log_file=' . $log_file . '">Logs</a>';
            array_unshift($links, $settings_link, $logs_link);
        }

        return $links;
    }

    function actionWoocommerceThankyouCryptomarket($order_id) {
        $wc_order = wc_get_order($order_id);

        if ($wc_order === false) {
            return;
        }

        $order_data = $wc_order->get_data();
        $status = $order_data['status'];

        $payment_status = file_get_contents(plugin_dir_path(__FILE__) . 'templates/paymentStatus.tpl');
        $payment_status = str_replace('{$statusTitle}', _x('Payment Status', 'woocommerce_cryptomarket'), $payment_status);

        switch ($status) {
        case 'on-hold':
            $status_desctiption = _x('Waiting for payment', 'woocommerce_cryptomarket');
            break;
        case 'processing':
            $status_desctiption = _x('Payment processing', 'woocommerce_cryptomarket');
            break;
        case 'completed':
            $status_desctiption = _x('Payment completed', 'woocommerce_cryptomarket');
            break;
        case 'failed':
            $status_desctiption = _x('Payment failed', 'woocommerce_cryptomarket');
            break;
        default:
            $status_desctiption = _x(ucfirst($status), 'woocommerce_cryptomarket');
            break;
        }

        echo str_replace('{$paymentStatus}', $status_desctiption, $payment_status);
    }
    add_action("woocommerce_thankyou_cryptomarket", 'action_woocommerce_thankyou_cryptomarket', 10, 1);
}

function woocommerceCryptomarketFailedRequirements() {
    global $wp_version;
    global $woocommerce;

    $errors = array();
    // PHP 5.4+ required
    if (true === version_compare(PHP_VERSION, '5.4.0', '<')) {
        $errors[] = 'Your PHP version is too old. The cryptomarket payment plugin requires PHP 5.4 or higher to function. Please contact your web server administrator for assistance.';
    }

    // Wordpress 3.9+ required
    if (true === version_compare($wp_version, '3.9', '<')) {
        $errors[] = 'Your WordPress version is too old. The cryptomarket payment plugin requires Wordpress 3.9 or higher to function. Please contact your web server administrator for assistance.';
    }

    // WooCommerce required
    if (true === empty($woocommerce)) {
        $errors[] = 'The WooCommerce plugin for WordPress needs to be installed and activated. Please contact your web server administrator for assistance.';
    } elseif (true === version_compare($woocommerce->version, '2.2', '<')) {
        $errors[] = 'Your WooCommerce version is too old. The cryptomarket payment plugin requires WooCommerce 2.2 or higher to function. Your version is ' . $woocommerce->version . '. Please contact your web server administrator for assistance.';
    }

    if (false === empty($errors)) {
        return implode("<br>\n", $errors);
    } else {
        return false;
    }

}

// Activating the plugin
function woocommerceCryptomarketActivate() {
    // Check for Requirements
    $failed = woocommerceCryptomarketFailedRequirements();

    $plugins_url = admin_url('plugins.php');

    // Requirements met, activate the plugin
    if ($failed === false) {

        // Deactivate any older versions that might still be present
        $plugins = get_plugins();

        foreach ($plugins as $file => $plugin) {
            if ('cryptomarket Woocommerce' === $plugin['Name'] && true === is_plugin_active($file)) {
                deactivate_plugins(plugin_basename(__FILE__));
                wp_die('cryptomarket for WooCommerce requires that the old plugin, <b>cryptomarket Woocommerce</b>, is deactivated and deleted.<br><a href="' . $plugins_url . '">Return to plugins screen</a>');

            }
        }

        update_option('woocommerce_cryptomarket_version', '2.2.14');

    } else {
        // Requirements not met, return an error message
        wp_die($failed . '<br><a href="' . $plugins_url . '">Return to plugins screen</a>');
    }
}
