<?php
/*
Plugin Name: Cryptomarket for WooCommerce
Plugin URI:  https://www.cryptomkt.com
Description: Accept multiple cryptocurrencies and turn into local currency as EUR, CLP, BRL and ARS on your WooCommerce store..
Author:      CryptoMarket Dev Team
Text Domain: CryptoMarket
Author URI:  https://www.cryptomkt.com

Version:           0.1
License:           Copyright 2016-2018 Cryptomarket SPA., MIT License
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
        public $client;

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
            $this->method_description = 'Accept multiple cryptocurrencies and turn into local currency as EUR, CLP, BRL and ARS on your WooCommerce store.';

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
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'save_order_states'));
            // Payment listener/API hook
            add_action('woocommerce_api_wc_gateway_cryptomarket', array(
                $this,
                'update_order_states'
            ));

            //Show setting errors
            if(function_exists('settings_errors')) settings_errors();

            // Valid for use and IPN Callback
            if (false === $this->is_valid_for_use()) {
                $this->enabled = 'no';
                $this->log('[Info] The plugin is NOT valid for use!');
            } else {
                $this->enabled = 'yes';
                $this->log('[Info] The plugin is ok to use.');
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
                    'default' => __('CryptoCompra by CryptoMarket', 'cryptomarket'),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('Customer Message', 'cryptomarket'),
                    'type' => 'textarea',
                    'description' => __('Message to explain how the customer will be paying for the purchase.', 'cryptomarket'),
                    'default' => 'You will be redirected to CryptoMarket to complete your purchase.',
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
                'order_states' => array(
                    'type' => 'order_states'
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
         * HTML output for form field type `order_states`
         */
        public function generate_order_states_html()
        {
            $this->log('    [Info] Entered generate_order_states_html()...');
            ob_start();
            $cm_statuses = array(
                'new'=>'New Order', 
                'waiting_pay'=>'Waiting for pay', 
                'waiting_block'=>'Waiting for block', 
                'waiting_processing' => 'Waiting processing', 
                'complete'=>'Successful payment', 
                'invalid'=>'Invalid');
            
            $df_statuses = array(
                'new'=>'wc-on-hold', 
                'waiting_pay'=>'wc-processing', 
                'waiting_block'=>'wc-processing', 
                'waiting_processing'=>'wc-processing', 
                'complete'=>'wc-completed', 
                'invalid'=>'wc-failed');
            $wc_statuses = wc_get_order_statuses();

            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">Order States:</th>
                <td class="forminp" id="cryptomarket_order_states">
                    <table cellspacing="0">
                        <?php
                            foreach ($cm_statuses as $cm_state => $cm_name) {
                            ?>
                            <tr>
                            <th><?php echo $cm_name; ?></th>
                            <td>
                                <select name="woocommerce_cryptomarket_order_states[<?php echo $cm_state; ?>]">
                                <?php
                                $order_states = get_option('woocommerce_cryptomarket_settings');
                                $order_states = $order_states['order_states'];
                                foreach ($wc_statuses as $wc_state => $wc_name) {
                                    $current_option = $order_states[$cm_state];
                                    if (true === empty($current_option)) {
                                        $current_option = $df_statuses[$cm_state];
                                    }
                                    if ($current_option === $wc_state) {
                                        echo "<option value=\"$wc_state\" selected>$wc_name</option>\n";
                                    } else {
                                        echo "<option value=\"$wc_state\">$wc_name</option>\n";
                                    }
                                }
                                ?>
                                </select>
                            </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </table>
                </td>
            </tr>
            <?php
            $this->log('[Info] Leaving generate_order_states_html()...');
            return ob_get_clean();
        }

        /**
         * [update_order_states wc-api update order status]
         */
        function update_order_states() {
            if (true === empty($_POST)) {
                $this->log('[Error] No post data sent to callback handler!');
                error_log('[Error] Plugin received empty POST data for an callback_url message.');
                wp_die('No post data');
            } else {
                $this->log('[Info] The post data sent from server is present...');
            }

            $payload = (object) $_POST;

            if (true === empty($payload)) {
                $this->log('[Error] Invalid JSON payload: ' . $post_body);
                error_log('[Error] Invalid JSON payload: ' . $post_body);
                wp_die('Invalid JSON');
            } else {
                $this->log('[Info] The post data was decoded into JSON...');
            }

            if (false === array_key_exists('id', $payload)) {
                $this->log('[Error] No ID present in payload: ' . var_export($payload, true));
                error_log('[Error] Plugin did not receive an Order ID present in payload: ' . var_export($payload, true));
                wp_die('No Order ID');
            } else {
                $this->log('[Info] Order ID present in payload...');
            }

            if (false === array_key_exists('external_id', $payload)) {
                $this->log('[Error] No Order ID present in payload: ' . var_export($payload, true));
                error_log('[Error] Plugin did not receive an Order ID present in payload: ' . var_export($payload, true));
                wp_die('No Order ID');
            } else {
                $this->log('[Info] Order ID present in JSON payload...');
            }

            $order_id = $payload->external_id; $this->log('[Info] Order ID:' . $order_id);
            $order = wc_get_order($order_id);
            $order_states = $this->get_option('order_states');

            if (false === $order || 'WC_Order' !== get_class($order)) {
                $this->log('[Error] The Plugin was called but could not retrieve the order details for order_id: "' . $order_id . '". If you use an alternative order numbering system, please see class-wc-gateway-cryptomarket.php to apply a search filter.');
                throw new \Exception('The Plugin was called but could not retrieve the order details for order_id ' . $order_id . '. Cannot continue!');
            } else {
                $this->log('[Info] Order details retrieved successfully...');
            }

            $current_status = $order->get_status();
            if (false === isset($current_status) && true === empty($current_status)) {
                $this->log('[Error] The Plugin was calledbut could not obtain the current status from the order.');
                throw new \Exception('The Plugin was called but could not obtain the current status from the order. Cannot continue!');
            } else {
                $this->log('[Info] The current order status for this order is ' . $current_status);
            }

            switch ($payload->status) {
                case "-4":
                    $this->log('[Info] Pago múltiple. Orden ID:'.$order_id);
                    $order->update_status($order_states['invalid']);

                    wp_die('Pago Multiple');
                    break;
                case "-3":
                    $this->log('[Info] Monto pagado no concuerda. Orden ID:'.$order_id);
                    $order->update_status($order_states['invalid']);

                    wp_die('Monto pagado no concuerda');
                    break;
                case "-2":
                    $this->log('[Info] Falló conversión. Orden ID:'.$order_id);
                    $order->update_status($order_states['invalid']);

                    wp_die('Falló conversión');
                    break;
                case "-1":
                    $this->log('[Info] Expiró orden de pago. Orden ID:'.$order_id);
                    $order->update_status($order_states['invalid']);

                    wp_die('Expiró orden de pago');
                    break;
                case "0":
                    $this->log('[Info] Esperando pago. Orden ID:'.$order_id);
                    $order->update_status($order_states['waiting_pay']);

                    break;
                case "1":
                    $this->log('[Info] Esperando bloque. Orden ID:'.$order_id);
                    $order->update_status($order_states['waiting_block']);

                    break;
                case "2":
                    $this->log('[Info] Esperando procesamiento. Orden ID:'.$order_id);
                    $order->update_status($order_states['waiting_processing']);

                    break;
                case "3":
                    $this->log('[Info] Pago exitoso. Orden ID:'.$order_id);
                    $order->update_status($order_states['complete']);
                    break;

                default:
                    $this->log('[Error] No status payment defined:'.$payload->status.'. Order ID:'.$order_id);
                    break;
            }
            exit;
        }

        /**
         * Save order states
         */
        public function save_order_states()
        {
            $this->log('[Info] Entered save_order_states()...');

            $cm_statuses = array(
                'new'=>'New Order', 
                'waiting_pay'=>'Waiting for pay', 
                'waiting_block'=>'Waiting for block', 
                'waiting_processing' => 'Waiting processing', 
                'complete'=>'Successful payment', 
                'invalid'=>'Invalid'
            );

            $wc_statuses = wc_get_order_statuses();
            if (true === isset($_POST['woocommerce_criptomarket_order_states'])) {
                $cm_settings = get_option('woocommerce_cryptomarket_settings');
                $order_states = $cm_settings['order_states'];
                foreach ($cm_statuses as $cm_state => $cm_name) {
                    if (false === isset($_POST['woocommerce_cryptomarket_order_states'][ $cm_state ])) {
                        continue;
                    }
                    $wc_state = $_POST['woocommerce_cryptomarket_order_states'][ $cm_state ];
                    if (true === array_key_exists($wc_state, $wc_statuses)) {
                        $this->log('[Info] Updating order state ' . $cm_state . ' to ' . $wc_state);
                        $order_states[$cm_state] = $wc_state;
                    }
                }
                $cm_settings['order_states'] = $order_states;
                update_option('woocommerce_cryptomarket_settings', $cm_settings);
            }
            $this->log('[Info] Leaving save_order_states()...');
        }

        /**
         * Validate Order States
         */
        public function validate_order_states_field()
        {
            $order_states = $this->get_option('order_states');
            if ( isset( $_POST[ $this->plugin_id . $this->id . '_order_states' ] ) ) {
                $order_states = $_POST[ $this->plugin_id . $this->id . '_order_states' ];
            }

            return $order_states;
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
            $min_value = (float) $result->data[0]->bid * 0.001;
            $total_order = (float) $order->get_total();

            if ($total_order > $min_value) {
                try {

                    $success_return_url = $this->get_return_url($order);

                    $payment = array(
                        'payment_receiver' => $this->get_option('payment_receiver'),
                        'to_receive_currency' => $currency_code,
                        'to_receive' => $total_order,
                        'external_id' => $order->get_id(),
                        'callback_url' => str_replace('https:', 'http:', add_query_arg('wc-api','WC_Gateway_cryptomarket', home_url( '/' ))),
                        'error_url' => WC()->cart->get_checkout_url(),
                        'success_url' => $success_return_url,
                        'refund_email' => $order->get_billing_email(),
                    );

                    $payload = $this->client->createPayOrder($payment);

                    if($payload->status === 'error'){
                        throw new \Exception($payload->message);
                    }
                    else{
                        // Mark new order according to user settings (we're awaiting the payment)
                        $new_order_states = $this->get_option('order_states');
                        $new_order_status = $new_order_states['new'];
                        $this->log('[Info] Changing order status to: '.$new_order_status);

                        $order->update_status($new_order_status);
                        $this->log('[Info] Changed order status result');
                                                
                        // Redirect the customer to the CryptoMarket invoice
                        return array(
                            'result'   => 'success',
                            'redirect' => $payload->data->payment_url
                        );
                    }
                } catch (Exception $e) {
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
            $status_description = _x('Waiting for payment', 'woocommerce_cryptomarket');
            break;
        case 'processing':
            $status_description = _x('Payment processing', 'woocommerce_cryptomarket');
            break;
        case 'completed':
            $status_description = _x('Payment completed', 'woocommerce_cryptomarket');
            break;
        case 'failed':
            $status_description = _x('Payment failed', 'woocommerce_cryptomarket');
            break;
        default:
            $status_description = _x(ucfirst($status), 'woocommerce_cryptomarket');
            break;
        }

        echo str_replace('{$paymentStatus}', $status_description, $payment_status);
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

        update_option('woocommerce_cryptomarket_version', '0.1');

    } else {
        // Requirements not met, return an error message
        wp_die($failed . '<br><a href="' . $plugins_url . '">Return to plugins screen</a>');
    }
}