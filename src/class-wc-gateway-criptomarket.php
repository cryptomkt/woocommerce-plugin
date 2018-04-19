<?php
/*
Plugin Name: Cryptomarket for WooCommerce
Plugin URI:  https://www.cryptomkt.com
Description: Enable your WooCommerce store to accept Ethereum with Cryptomarket.
Author:      Cryptomarket Team
Text Domain: Cryptomarket
Author URI:  https://www.cryptomkt.com

Version:           1.1.12
License:           Copyright 2011-2018 cryptomarket Inc., MIT License
License URI:       https://github.com/cryptomkt/woocommerce-plugin/blob/master/LICENSE
GitHub Plugin URI: https://github.com/cryptomkt/woocommerce-plugin
 */

// Exit if accessed directly
if (false === defined('ABSPATH')) {
    exit;
}

//composer autoload
$loader = require __DIR__ . '/lib/autoload.php';
$loader->add('Cryptomkt\\Exchange\\Client', __DIR__);
$loader->add('Cryptomkt\\Exchange\\Configuration as CMConfiguration', __DIR__);

// Ensures WooCommerce is loaded before initializing the cryptomarket plugin
add_action('plugins_loaded', 'woocommerce_cryptomarket_init', 0);
register_activation_hook(__FILE__, 'woocommerce_cryptomarket_activate');

function woocommerce_cryptomarket_init() {
    class WC_Gateway_cryptomarket extends WC_Payment_Gateway {
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
            $this->method_title = 'Cryptomarket';
            $this->method_description = 'Cryptomarket allows you to accept Ethereum payments on your WooCommerce store.';

            // Load the settings.
            $this->init_form_fields();
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
            $this->log('[Info] $this->payment_receiver   = ' . $this->get_option('payment_receiver'));
            $this->log('[Info] $this->api_key            = ' . $this->get_option('api_key'));
            $this->log('[Info] $this->api_secret         = ' . $this->get_option('api_secret'));

            // Actions
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            // add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'save_order_states'));
            
            //Show setting errors
            if(function_exists('settings_errors')) settings_errors();

            // Valid for use and IPN Callback
            if (false === $this->is_valid_for_use()) {
                $this->enabled = 'no';
                $this->log('[Info] The plugin is NOT valid for use!');
            } else {
                $this->enabled = 'yes';
                $this->log('[Info] The plugin is ok to use.');
                // add_action('woocommerce_api_wc_gateway_cryptomarket', array($this, 'ipn_callback'));
            }

            $this->is_initialized = true;
        }

        public function __destruct() {
        }

        public function is_valid_for_use() {
            // Check that API credentials are set
            if (empty($this->get_option('payment_receiver')) ||
                empty($this->get_option('api_key')) ||
                empty($this->get_option('api_secret'))) {
                return false;
            }

            // Setup the cryptomarket client
            $configuration = Cryptomkt\Exchange\Configuration::apiKey(
                $this->get_option('api_key'), 
                $this->get_option('api_secret')
                );
            $this->client = Cryptomkt\Exchange\Client::create($configuration);

            // Ensure the currency is supported by cryptomarket
            $currency = get_woocommerce_currency();

            try {
                $result = $this->client->getTicker(array('market' => 'ETH' . $currency));
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
        public function init_form_fields() {
            $this->log('[Info] Entered init_form_fields()...');
            $log_file = 'cryptomarket-' . sanitize_file_name(wp_hash('cryptomarket')) . '-log';
            $logs_href = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-status&tab=logs&log_file=' . $log_file;

            $this->form_fields = array(
                'title' => array(
                    'title' => __('Title', 'cryptomarket'),
                    'type' => 'text',
                    'description' => __('Controls the name of this payment method as displayed to the customer during checkout.', 'cryptomarket'),
                    'default' => __('CryptoMarket | Ethereum', 'cryptomarket'),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('Customer Message', 'cryptomarket'),
                    'type' => 'textarea',
                    'description' => __('Message to explain how the customer will be paying for the purchase.', 'cryptomarket'),
                    'default' => 'You will be redirected to cryptomkt.com to complete your purchase.',
                    'desc_tip' => true,
                ),
                'payment_receiver' => array(
                    'title' => __('Payment Receiver', 'cryptomarket'),
                    'type' => 'paymentreceivertext',
                    'description' => __('Email from .', 'cryptomarket'),
                    'default' => __('user@domain.com', 'cryptomarket'),
                    'desc_tip' => true
                ),
                'api_key' => array(
                    'title' => __('API Key', 'cryptomarket'),
                    'type' => 'apikeytext',
                    'description' => __('API Key of you CryptoMarket account.', 'cryptomarket'),
                    'desc_tip' => true
                ),
                'api_secret' => array(
                    'title' => __('API Secret', 'cryptomarket'),
                    'type' => 'apisecrettext',
                    'description' => __('API Secret of you CryptoMarket account.', 'cryptomarket'),
                    'desc_tip' => true
                ),
                'debug' => array(
                    'title' => __('Debug Log', 'cryptomarket'),
                    'type' => 'checkbox',
                    'label' => sprintf(__('Enable logging <a href="%s" class="button">View Logs</a>', 'cryptomarket'), $logs_href),
                    'default' => 'no',
                    'description' => sprintf(__('Log cryptomarket events, inside <code>%s</code>', 'cryptomarket'), wc_get_log_file_path('cryptomarket')),
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
         * Validate Payment Receiver
         */
        public function validate_paymentreceivertext_field($key, $value) {
            if( true === empty($value) ){
                add_settings_error($key, esc_attr( 'settings_updated' ), 'Payment Receiver value is empty', 'error');
            }else{
                return $value;
            }
        }

        /**
         * Validate API Key
         */
        public function validate_apikeytext_field($key, $value) {
            if( true === empty($value) ){
                add_settings_error($key, esc_attr( 'settings_updated' ), 'API Key value is empty', 'error');
            }else{
                return $value;
            }
        }

        /**
         * Validate API Secret
         */
        public function validate_apisecrettext_field($key, $value) {
            if( true === empty($value) ){
                add_settings_error($key, esc_attr( 'settings_updated' ), 'API Secret value is empty', 'error');
            }else{
                return $value;
            }
        }

        /**
         * Process the payment and return the result
         *
         * @param   int     $order_id
         * @return  array
         */
        public function process_payment($order_id) {
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

            if (true === empty($this->get_option('api_key')) || true === empty($this->get_option('api_secret'))) {
                $this->log('[Error] The API Credentials is missing.');
                throw new \Exception('The API Credentials is missing, please set in WooCommerce checkout configuration.');
            }

            // Setup the currency
            $currency_code = get_woocommerce_currency();
            
            try {
                $result = $this->client->getTicker(array('market' => 'ETH' . $currency_code));
            } catch (Exception $e) {
                throw new \Exception('Currency does not supported: ' . $currency_code);
            }            

            //Min value validation
            $min_value = (float) $result[0]['bid'] * 0.001;
            $total_order = (float) $order->get_total();

            if ($total_order > $min_value) {
                try {
                    // $order->update_status('on-hold', __( 'Awaiting ethereum payment', 'woocommerce' ));
                    $success_return_url = $this->get_return_url($order);

                    $payment = array(
                        'payment_receiver' => $this->get_option('payment_receiver'),
                        'to_receive_currency' => $currency_code,
                        'to_receive' => $total_order,
                        'external_id' => $order->get_transaction_id(),
                        'callback_url' => $success_return_url,
                        'error_url' => WC()->cart->get_checkout_url(),
                        'success_url' => $success_return_url,
                        'refund_email' => $order->get_billing_email(),
                    );

                    $payload = $this->client->createPayOrder($payment);

                    // Redirect the customer to the CryptoMarket invoice
                    return array(
                        'result'   => 'success',
                        'redirect' => $payload['payment_url'],
                    );
                    
                    
                } catch (Exception $e) {
                    $this->log('--------->'.$this->get_option('api_key'));
                    
                    throw new \Exception($e->getMessage());
                }
            } else {
                throw new \Exception('Total order must be greater than ' . $min_value);
            }


            $this->log('[Info] Leaving process_payment()...');

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
    function wc_add_cryptomarket($methods) {
        $methods[] = 'WC_Gateway_cryptomarket';

        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'wc_add_cryptomarket');

    /**
     * Add Settings link to the plugin entry in the plugins menu
     **/
    add_filter('plugin_action_links', 'cryptomarket_plugin_action_links', 10, 2);

    function cryptomarket_plugin_action_links($links, $file) {
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

    function action_woocommerce_thankyou_cryptomarket($order_id) {
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

function woocommerce_cryptomarket_failed_requirements() {
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
function woocommerce_cryptomarket_activate() {
    // Check for Requirements
    $failed = woocommerce_cryptomarket_failed_requirements();

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