<?php
/*
    Plugin Name: LND for WooCommerce
    Plugin URI:  https://github.com/agustinkassis/lnd-woocommerce
    Text Domain: lnd-woocommerce
    Domain Path: /languages
    Description: Enable instant and fee-reduced payments in BTC through Lightning Network.
    Author:      Agustin Kassis
    Author URI:  https://github.com/agustinkassis
    Version:           0.1.0
    GitHub Plugin URI: https://github.com/agustinkassis/lnd-woocommerce
*/

// Exchanges Tickers for ARS
require('exchanges/abstract.php');

require('exchanges/satoshitango.php');
require('exchanges/ripio.php');
require('exchanges/bitso.php');
require('exchanges/bitex.php');

$exchangesList = [
  'satoshi_tango' => new SatoshiTango(),
  'ripio' => new Ripio(),
  'bitso' => new Bitso(),
  'bitex' => new Bitex(),
];

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

register_activation_hook( __FILE__, function(){
  if (!extension_loaded('gd') || !extension_loaded('curl')) {
    die('The php-curl and php-gd extensions are required. Please contact your hosting provider for additional help.');
  }
});

require_once 'Lnd_wrapper.php';


// function debug_load_textdomain( $domain , $mofile  ){
//     echo "Trying ",$domain," at ",$mofile,"<br />\n";
// }
// add_action('load_textdomain','debug_load_textdomain');

if (!function_exists('init_wc_lightning')) {

  function init_wc_lightning() {
    if (!class_exists('WC_Payment_Gateway')) return;

    class WC_Gateway_Lightning extends WC_Payment_Gateway {

      public function __construct() {
        $this->id                 = 'lightning';
        $this->order_button_text  = __('Proceed to Lightning Payment', 'lnd-woocommerce');
        $this->method_title       = __('Lightning', 'lnd-woocommerce');
        $this->method_description = __('Lightning Network Payment', 'lnd-woocommerce');
        $this->icon               = plugin_dir_url(__FILE__).'img/logo.png';
        $this->supports           = array();

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title       = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->endpoint = $this->get_option( 'endpoint' );
        $this->macaroon = $this->get_option( 'macaroon' );
        $this->lndCon = LndWrapper::instance();
        $this->lndCon->setCredentials ( $this->get_option( 'endpoint' ), $this->get_option( 'macaroon' ), $this->get_option( 'ssl' ));

        add_action('woocommerce_payment_gateways', array($this, 'register_gateway'));
        add_action('woocommerce_update_options_payment_gateways_lightning', array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_lightning', array($this, 'show_payment_info'));
        add_action('woocommerce_thankyou_lightning', array($this, 'show_payment_info'));
        add_action('wp_ajax_ln_wait_invoice', array($this, 'wait_invoice'));
        add_action('wp_ajax_nopriv_ln_wait_invoice', array($this, 'wait_invoice'));
      }

      /**
       * Initialise Gateway Settings Form Fields.
       */
      public function init_form_fields() {
        global $exchangesList;

        $tlsPath = plugin_dir_path(__FILE__).'tls/tls.cert';
        $this->form_fields = array(
          'enabled' => array(
            'title'       => __( 'Enable/Disable', 'lnd-woocommerce' ),
            'label'       => __( 'Enable Lightning payments', 'lnd-woocommerce' ),
            'type'        => 'checkbox',
            'description' => '',
            'default'     => 'no',
          ),
          'title' => array(
            'title'       => __('Title'),
            'type'        => 'text',
            'description' => __('Controls the name of this payment method as displayed to the customer during checkout.', 'lnd-woocommerce'),
            'default'     => __('Bitcoin Lightning', 'lnd-woocommerce'),
            'desc_tip'    => true,
          ),
          'ticker' => array(
            'title'       => __('Ticker', 'lnd-woocommerce'),
            'type'        => 'select',
            'description' => __('Select Exchange for rate calculation.', 'lnd-woocommerce'),
            'default'     => 'BTC',
            'options'     => array_map(function($exchange) {
              return $exchange->name;
            }, $exchangesList),
            'desc_tip'    => true,
          ),
          'rate_markup' => array(
            'title'       => __('Rate Markup', 'lnd-woocommerce'),
            'type'        => 'text',
            'description' => __('Increases exchange rate with a percentage', 'lnd-woocommerce'),
            'default'     => 1, // 5 minutes
            'desc_tip'    => true,
          ),
          'invoice_expiry' => array(
            'title'       => __('Invoice Expiration', 'lnd-woocommerce'),
            'type'        => 'text',
            'description' => __('Invoice expiration time in seconds', 'lnd-woocommerce'),
            'default'     => 300, // 5 minutes
            'desc_tip'    => true,
          ),
          'endpoint' => array(
            'title'       => __( 'Endpoint', 'lnd-woocommerce' ),
            'type'        => 'textarea',
            'description' => __( 'Place here the API endpoint', 'lnd-woocommerce' ),
            'default'     => 'https://localhost:8080',
            'desc_tip'    => true,
          ),
          'macaroon' => array(
            'title'       => __('Macaroon Hex', 'lnd-woocommerce'),
            'type'        => 'textarea',
            'description' => __('Input Macaroon Hex to get access to LND API', 'lnd-woocommerce'),
            'default'     => '',
            'desc_tip'    => true,
          ),
          'description' => array(
            'title'       => __('Customer Message', 'lnd-woocommerce'),
            'type'        => 'textarea',
            'description' => __('Message to explain how the customer will be paying for the purchase.', 'lnd-woocommerce'),
            'default'     => __('You will pay using the Lightning Network.', 'lnd-woocommerce'),
            'desc_tip'    => true,
          ),
          'ssl' => array(
            'title'       => __('SSL Certificate Path', 'lnd-woocommerce'),
            'type'        => 'textarea',
            'description' => __('Put your LND SSL certificate path.', 'lnd-woocommerce'),
            'default'     => $tlsPath,
            'desc_tip'    => true,
          )

        );
      }
      /**
       * Get ticker from ARS Exchanges
       * @return float Price
       */
      public function getAlternativePrice() {
        global $exchangesList;
        $price = $exchangesList[$this->get_option('ticker')]->getPrice();
        // echo "Price Modafakaaa!! : $price";
        // die();
        return $price;
      }

      public function getTicker($addMarkup=false) {
        $currency = get_woocommerce_currency();
        $rate = ($currency == 'ARS') ? $this->getAlternativePrice() : $this->lndCon->getLivePrice();

        $markup = 0;
        if ($addMarkup) {
          $markup = (float) $this->get_option('rate_markup');
          $rate = $rate/(1+$markup/100);
        }

        return (object) array(
          'currency' => $currency,
          'rate' => $rate,
          'markup' => $markup
        );
      }

      public function create_invoice($order, $ticker) {
        $livePrice = $ticker->rate;

        $invoiceInfo = array();
        $btcPrice = $order->get_total() * ((float)1/ $livePrice);

        $invoiceInfo['value'] = round($btcPrice * 100000000);
        $invoiceInfo['expiry'] = $this->get_option('invoice_expiry');
        $invoiceInfo['memo'] = "Order key: " . $order->get_checkout_order_received_url();

        return $this->lndCon->createInvoice ( $invoiceInfo );
      }

      public function updatePostMeta($order, $invoice, $ticker) {
        update_post_meta( $order->get_id(), 'LN_RATE', $ticker->rate);
        update_post_meta( $order->get_id(), 'LN_EXCHANGE', $this->get_option('ticker'));
        update_post_meta( $order->get_id(), 'LN_AMOUNT', $invoice->value);
        update_post_meta( $order->get_id(), 'LN_INVOICE', $invoice->payment_request);
        update_post_meta( $order->get_id(), 'LN_HASH', $invoice->r_hash);
        $order->add_order_note(__('Awaiting payment of ', 'lnd-woocommerce') . number_format((float)$invoice->value, 7, '.', '') . " BTC@ 1 BTC ~ " . $ticker->rate ."(+" . $ticker->markup . "%) " . $ticker->currency . ". <br> Invoice ID: " . $invoice->payment_request);
      }

      /**
       * Process the payment and return the result.
       * @param  int $order_id
       * @return array
       */
      public function process_payment( $order_id ) {
        $order = wc_get_order($order_id);
        $ticker = $this->getTicker(true);
        $invoice = $this->create_invoice($order, $ticker);

        if(property_exists($invoice, 'error')){
          wc_add_notice( __('Error: ') . $invoice->error, 'error' );
          return;
        }

        if(!property_exists($invoice, 'payment_request')) {
          wc_add_notice( __('Error: ') . __('Lightning Node is not reachable at this time. Please contact the store administrator.', 'lnd-woocommerce'), 'error' );
          return;
        }

        $this->updatePostMeta($order, $invoice, $ticker);

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

        if($order->get_status() == 'processing'){
          status_header(200);
          wp_send_json(true);
          return;
        }
        /**
         *
         * Check if invoice is paid
         */

        $payHash = get_post_meta( $_POST['invoice_id'], 'LN_HASH', true );

        $callResponse = $this->lndCon->getInvoiceInfoFromHash( bin2hex(base64_decode( $payHash ) ) );
        if(!property_exists( $callResponse, 'r_hash' )) {
          status_header(410);
          wp_send_json(false);
          return;
        }

        $invoiceTime = $callResponse->creation_date + $callResponse->expiry;

        if($invoiceTime < time()) {

          //Invoice expired
          $ticker = $this->getTicker(true);
          $invoice = $this->create_invoice($order, $ticker);
          $this->updatePostMeta($order, $invoice, $ticker);

          status_header(410);
          wp_send_json(false);
          return;
        }

        if(!property_exists( $callResponse, 'settled' )){
          status_header(402);
          wp_send_json(false);
          return;
        }

        if ($callResponse->settled) {
          $order->payment_complete();
          $order->add_order_note('Lightning Payment received on ' . $callResponse->settle_date);
          status_header(200);
          wp_send_json(true);
          return;
        }

      }

      /**
       * Hooks into the checkout page to display Lightning-related payment info.
       */
      public function show_payment_info($order_id) {
        global $wp, $exchangesList;

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

        if ($order->needs_payment()) {
          //Prepare information for payment page
          $qr_uri = $this->lndCon->generateQr( get_post_meta( $order_id, 'LN_INVOICE', true ) );
          $payHash = get_post_meta( $order_id, 'LN_HASH', true );
          $rate = number_format((float)get_post_meta( $order_id, 'LN_RATE', true ), 2, '.', ',');
          $exchange = $exchangesList[get_post_meta( $order_id, 'LN_EXCHANGE', true )]->name;
          $currency = $order->get_currency();
          $callResponse = $this->lndCon->getInvoiceInfoFromHash( bin2hex(base64_decode($payHash)) );
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

      protected static function format_msat($msat) {
        return rtrim(rtrim(number_format($msat/100000000, 8), '0'), '.') . ' BTC';
      }
    }

    new WC_Gateway_Lightning();
  }

  add_action('plugins_loaded', 'init_wc_lightning');
}
