<?
/*
    Plugin Name: LaWallet for WooCommerce
    Plugin URI:  https://github.com/lawalletio/lawallet-woocommerce
    Text Domain: lawallet-woocommerce
    Domain Path: /languages
    Description: Enable instant and fee-reduced payments in BTC through Lightning Network.
    Author:      Agustin Kassis
    Author URI:  https://github.com/agustinkassis
    Version:           0.1.0
    GitHub Plugin URI: https://github.com/lawalletio/lawallet-woocommerce
*/

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! defined( 'WC_LND_BASENAME' ) ) {
  define('WC_LND_NAME', 'lawallet-woocommerce');
  define('WC_LND_BASENAME', plugin_basename( __FILE__ ));
  define('WC_LND_PLUGIN_PATH', plugin_dir_path(__FILE__));
  define('WC_LND_PLUGIN_URL', plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__)));
  define('WC_LND_VERSION', '0.9.1');
}

// register_activation_hook( __FILE__, function(){
//   if (!extension_loaded('gd') || !extension_loaded('curl')) {
//     die('The php-curl and php-gd extensions are required. Please contact your hosting provider for additional help.');
//   }
// });

// Providers
require_once(WC_LND_PLUGIN_PATH . 'includes/TickerManager.php');
// require_once(WC_LND_PLUGIN_PATH . 'admin/LND_Woocommerce_Admin.php');

if (!function_exists('init_wc_lightning')) {

  function init_wc_lightning() {
    if (!class_exists('WC_Payment_Gateway')) return;

    class WC_Gateway_Lightning extends WC_Payment_Gateway {
      private TickerManager $tickerManager;
      // private LND_Woocommerce_Admin $admin;

      public function __construct() {
        // Setup general properties
        $this->setup_properties();

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title       = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = true;

        add_filter('woocommerce_payment_gateways', array($this, 'register_gateway'));

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action('woocommerce_receipt_lightning', array($this, 'show_payment_info'));
        add_action('woocommerce_thankyou_lightning', array($this, 'show_payment_info'));
        add_action('wp_ajax_ln_wait_invoice', array($this, 'wait_invoice'));
        add_action('wp_ajax_nopriv_ln_wait_invoice', array($this, 'wait_invoice'));


        // Is Admin
        if (is_admin()) {
          // $this->admin = LND_Woocommerce_Admin::instance();
          // $this->admin->set_gateway($this);

          if ($this->is_manage_section()) {
            add_action('admin_enqueue_scripts', array($this, 'load_admin_script'));
          }
        }
      }

      public function is_available() {
        return true;
      }

      /**
       * Setup general properties for the gateway.
       */
      protected function setup_properties() {
        $this->id                 = 'lightning';
        $this->order_button_text  = __('Proceed to LaWallet', 'lawallet-woocommerce');
        $this->method_title       = __('LaWallet', 'lawallet-woocommerce');
        $this->method_description = __('Lightning Network Payment', 'lawallet-woocommerce');
        $this->icon               = plugin_dir_url(__FILE__).'assets/img/logo.png';
        $this->supports           = array('products');
        $this->has_fields         = false;
      }

      /**
       * Initialise Gateway Settings Form Fields.
       */
      public function init_form_fields() {
        $this->tickerManager = TickerManager::instance();

        $this->form_fields = array(
          'enabled' => array(
            'title'       => __( 'Enable/Disable', 'lawallet-woocommerce' ),
            'label'       => __( 'Enable Lightning payments', 'lawallet-woocommerce' ),
            'type'        => 'checkbox',
            'description' => '',
            'default'     => 'no',
          ),
          'title' => array(
            'title'       => __('Title'),
            'type'        => 'text',
            'description' => __('Controls the name of this payment method as displayed to the customer during checkout.', 'lawallet-woocommerce'),
            'default'     => __('Lawallet', 'lawallet-woocommerce'),
            'desc_tip'    => true,
          ),
          'ticker' => array(
            'title'       => __('Ticker', 'lawallet-woocommerce'),
            'type'        => 'select',
            'description' => __('Select Exchange for rate calculation.', 'lawallet-woocommerce'),
            'default'     => 'satoshi_tango',
            'options'     => array_map(function($exchange) {
              return $exchange->name;
            }, $this->tickerManager->getValid()),
            'desc_tip'    => true,
          ),
          'rate_markup' => array(
            'title'       => __('Rate Markup', 'lawallet-woocommerce'),
            'type'        => 'text',
            'description' => __('Increases exchange rate with a percentage', 'lawallet-woocommerce'),
            'default'     => 1, // 5 minutes
            'desc_tip'    => true,
          ),
          'invoice_expiry' => array(
            'title'       => __('Invoice Expiration', 'lawallet-woocommerce'),
            'type'        => 'text',
            'description' => __('Invoice expiration time in seconds', 'lawallet-woocommerce'),
            'default'     => 300, // 5 minutes
            'desc_tip'    => true,
          ),
          'description' => array(
            'title'       => __('Customer Message', 'lawallet-woocommerce'),
            'type'        => 'textarea',
            'description' => __('Message to explain how the customer will be paying for the purchase.', 'lawallet-woocommerce'),
            'default'     => __('You will pay using the Lightning Network.', 'lawallet-woocommerce'),
            'desc_tip'    => true,
          ),

        );
      }

      /**
       * Creates an invoice in the Lightning Network
       * @param  object $order  Order data
       * @param  array $ticker Ticker data
       * @return object         Invoice data
       */
      public function create_invoice($order, $ticker) {
        $livePrice = $ticker->rate;

        $invoiceInfo = array();
        $btcPrice = $order->get_total() * ((float)1/ $livePrice);

        $invoiceInfo['value'] = round($btcPrice * 100000000);
        $invoiceInfo['expiry'] = $this->get_option('invoice_expiry');
        $invoiceInfo['memo'] = "Order key: " . $order->get_checkout_order_received_url();

        $this->provider->authenticate();
        $invoice = $this->provider->createInvoice($invoiceInfo);

        $invoice->value = $invoiceInfo['value'];
        return $invoice;
      }

      /**
       * Updates post meta with LN meta data and adds order notes to the order
       * @param  object $order   Order data
       * @param  object $invoice Invoice data
       * @param  array $ticker  Ticker data
       */
      public function update_post_meta($order, $invoice, $ticker) {
        update_post_meta( $order->get_id(), 'LN_RATE', $ticker->rate);
        update_post_meta( $order->get_id(), 'LN_EXCHANGE', $this->get_option('ticker'));
        update_post_meta( $order->get_id(), 'LN_AMOUNT', $invoice->value);
        update_post_meta( $order->get_id(), 'LN_INVOICE', $invoice->payment_request);
        update_post_meta( $order->get_id(), 'LN_HASH', $invoice->payment_hash);
        update_post_meta( $order->get_id(), 'LN_EXPIRY', $invoice->expiry);
        update_post_meta( $order->get_id(), 'LN_INVOICE_JSON', json_encode($invoice));

        $order->add_order_note('LN_HASH: ' . $invoice->payment_hash);

        $btcPrice = $this->format_msat($invoice->value);
        $order->add_order_note(__('Awaiting payment of', 'lawallet-woocommerce') . ' ' .  $invoice->value . ' sats (' . $btcPrice . ')' . " @ 1 BTC ~ " . number_format($ticker->rate, 2) . " " . $ticker->currency . " (+" . $ticker->markup . "% " . __("applied", "lawallet-woocommerce") . "). <br> Invoice ID: " . $invoice->payment_request);
      }

      /**
       * Process the payment and return the result.
       * @param  int $order_id
       * @return array
       */
      public function process_payment( $order_id ) {
        $order = wc_get_order($order_id);
        try {
          $ticker = $this->tickerManager->getTicker($this->get_option('rate_markup'));
        } catch (\Exception $e) {
          wc_add_notice( __('Error: ') . __('Couldn\'t get quote from ticker.', 'lawallet-woocommerce'), 'error' );
          return;
        }

        //$order->add_order_note(json_encode($ticker));
        try {
          $invoice = $this->create_invoice($order, $ticker);
        } catch (\Exception $e) {
          $invoice = (object) [
            "error" => $e->getMessage()
          ];
        }


        if(property_exists($invoice, 'error')){
          wc_add_notice( __('Error: ') . $invoice->error, 'error' );
          return;
        }

        if(!property_exists($invoice, 'payment_request')) {
          wc_add_notice( __('Error: ') . __('Lightning Node is not reachable at this time. Please contact the store administrator.', 'lawallet-woocommerce'), 'error' );
          return;
        }

        $this->update_post_meta($order, $invoice, $ticker);

        return array(
          'result'   => 'success',
          'redirect' => $order->get_checkout_payment_url(true)
        );
      }

      /**
       * JSON endpoint for long polling payment updates.
       */
      public function wait_invoice() {
        $order = wc_get_order($_POST['invoice_id']);

        if($order->get_status() == 'processing') {
          status_header(200);
          wp_send_json(true);
          return;
        }

        /**
         * Check if invoice is paid
         */
        $invoiceTime = intval(get_post_meta( $_POST['invoice_id'], 'LN_EXPIRY', true ));
        if($invoiceTime < time()) {
          //Invoice expired
          try {
            $ticker = $this->tickerManager->getTicker($this->get_option('rate_markup'));
          } catch (\Exception $e) { // Can't get ticker
            status_header(500);
            wp_send_json(false);
            return;
          }

          $order->add_order_note(json_encode($ticker)); // Remove
          $invoice = $this->create_invoice($order, $ticker);
          $this->update_post_meta($order, $invoice, $ticker);

          status_header(410);
          wp_send_json(false);
          return;
        }

        $payHash = get_post_meta( $_POST['invoice_id'], 'LN_HASH', true );
        //TODO: Set provider of invoice
        try {
          if($this->check_payment($payHash)) {
            $order->payment_complete();
            $order->add_order_note('Lightning Payment received on ' . $callResponse->settle_date);
            status_header(200);
            wp_send_json(true);
          } else {
            status_header(402);
            wp_send_json(false);
          }
        } catch (\Exception $e) {
          status_header(500);
          wp_send_json(false);
        }
      }

      /**
       * Hooks into the checkout page to display Lightning-related payment info.
       */
      public function show_payment_info($order_id) {
        global $wp;

        $order = wc_get_order($order_id);

        if (!empty($wp->query_vars['order-received']) && $order->needs_payment()) {
          // thankyou page requested, but order is still unpaid
          wp_redirect($order->get_checkout_payment_url(true));
          exit;
        }

        if ($order->has_status('cancelled')) {
          // invoice expired, reload page to display expiry message
          wp_redirect($order->get_checkout_payment_url(true));
          exit;
        }

        //TODO: get_metadata function gets all in one query
        $payReq = get_post_meta( $order_id, 'LN_INVOICE', true);
        $payHash = get_post_meta( $order_id, 'LN_HASH', true );
        $sats = get_post_meta( $order_id, 'LN_AMOUNT', true);

        if ($order->needs_payment()) {
          //Prepare information for payment page

          $expiry = get_post_meta( $order_id, 'LN_EXPIRY', true);
          $rate = number_format((float)get_post_meta( $order_id, 'LN_RATE', true ), 2, '.', ',');
          $exchange = $this->tickerManager->getAll()[get_post_meta( $order_id, 'LN_EXCHANGE', true )]->name;
          $qr_uri = $this->generate_qr($payReq); // TODO: Generate it on clientside
          $currency = $order->get_currency();
          require __DIR__.'/templates/payment.php';

        } elseif ($order->has_status(array('processing', 'completed'))) {
          require __DIR__.'/templates/completed.php';
        }
      }

      /**
       * Register as a WooCommerce gateway.
       */
      public function register_gateway($methods) {
        $methods[] = $this;
        return $methods;
      }

      private function check_payment($paymentHash) {
        $this->provider->authenticate();
        return $this->provider->checkPayment($paymentHash);
      }

      /**
       * Loads js scripts
       */
      public function load_admin_script() {
      	wp_register_script( WC_LND_NAME, plugins_url( 'assets/js/script.js', __FILE__ ));
        wp_enqueue_script(WC_LND_NAME);
      }

      /**
       * Converts sats into BTC
       * @param  [float] $msat Amount in Satoshis
       * @return [float]       BTC denomination
       */

      protected static function format_msat($msat) {
        //var_dump($msat);
        //die();
        return rtrim(rtrim(number_format($msat/100000000, 8), '0'), '.') . ' BTC';
      }

      /**
       * Returns true if the user is plugin's settings page
       * @return boolean
       */
      protected function is_manage_section() {
        return (isset($_GET['section']) && $_GET['section']==='lightning');
      }

      /**
       * Generates QR code image with Google's API
       * @param  string $paymentRequest Invoice payment request
       * @return string                 Remote Image's URL
       */
      private function generate_qr($paymentRequest) {
          $size = "300x300";
          $margin = "0";
          $encoding = "UTF-8";
          return 'https://chart.googleapis.com/chart?cht=qr' . '&chs=' . $size . '&chld=|' . $margin . '&chl=' . $paymentRequest . '&choe=' . $encoding;
      }

      /**
       * Generates endpoint URL
       * @param  array $settings Plugin settings
       * @return string          Endpoint URL
       */
      public function generate_endpoint($settings) {
        $protocol = $settings['ssl'] ? 'https' : 'http';
        $host = $settings['host'] ? $settings['host'] : '';
        $port = $settings['port'] ? $settings['port'] : ($protocol == 'https' ? '443' : '80');
        return $protocol . '://' . $host . ':' . $port;
      }
    }

    new WC_Gateway_Lightning();
  }

  add_action('plugins_loaded', 'init_wc_lightning');
}
