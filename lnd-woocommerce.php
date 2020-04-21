<?
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

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! defined( 'WC_LND_BASENAME' ) ) {
  define('WC_LND_NAME', 'lnd-woocommerce');
  define('WC_LND_BASENAME', plugin_basename( __FILE__ ));
  define('WC_LND_PLUGIN_PATH', plugin_dir_path(__FILE__));
  define('WC_LND_PLUGIN_URL', plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__)));
  define('WC_LND_VERSION', '0.9.1');

  define('WC_LND_TLS_FILE', WC_LND_PLUGIN_PATH . 'creds/lnd.cert');
  define('WC_LND_MACAROON_FILE', WC_LND_PLUGIN_PATH . 'creds/lnd.macaroon');
}

register_activation_hook( __FILE__, function(){
  if (!extension_loaded('gd') || !extension_loaded('curl')) {
    die('The php-curl and php-gd extensions are required. Please contact your hosting provider for additional help.');
  }
});

// Providers
require_once(WC_LND_PLUGIN_PATH . 'includes/LndWrapper.php');
require_once(WC_LND_PLUGIN_PATH . 'includes/LndHub.php');
require_once(WC_LND_PLUGIN_PATH . 'includes/LND_WC_Helpers.php');

require_once(WC_LND_PLUGIN_PATH . 'includes/TickerManager.php');
require_once(WC_LND_PLUGIN_PATH . 'admin/LND_Woocommerce_Admin.php');

if (!function_exists('init_wc_lightning')) {

  function init_wc_lightning() {
    if (!class_exists('WC_Payment_Gateway')) return;

    class WC_Gateway_Lightning extends WC_Payment_Gateway {

      public function __construct() {
        $this->id                 = 'lightning';
        $this->order_button_text  = __('Proceed to Lightning Payment', 'lnd-woocommerce');
        $this->method_title       = __('Lightning', 'lnd-woocommerce');
        $this->method_description = __('Lightning Network Payment', 'lnd-woocommerce');
        $this->icon               = plugin_dir_url(__FILE__).'assets/img/logo.png';
        $this->supports           = array();

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title       = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->endpoint = LND_WC_Helpers::generateEndpoint($this->get_lnd_config());

        $this->settings = get_option(WC_LND_NAME, ['enabled'=> true, 'provider' => 'lnd']);

        $this->tickerManager = TickerManager::instance();

        try {
          $this->setup_provider();
          $this->tickerManager->setExchange($this->get_option('ticker'));
        } catch (\Exception $e) {
          $this->enabled = 'no';
        }

        add_filter('plugin_action_links_' . WC_LND_BASENAME, array($this, 'lndwoocommerce_settings_link'));

        add_action('woocommerce_payment_gateways', array($this, 'register_gateway'));
        add_action('woocommerce_update_options_payment_gateways_lightning', array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_lightning', array($this, 'show_payment_info'));
        add_action('woocommerce_thankyou_lightning', array($this, 'show_payment_info'));
        add_action('wp_ajax_ln_wait_invoice', array($this, 'wait_invoice'));
        add_action('wp_ajax_nopriv_ln_wait_invoice', array($this, 'wait_invoice'));

        add_action('wp_ajax_ln_upload_file', array($this, 'upload_file'));

        if (is_admin()) {
          $this->admin = LND_Woocommerce_Admin::instance();
        }

        if (is_admin() && $this->is_manage_section()) {
          add_action('admin_enqueue_scripts', array($this, 'load_admin_script'));
        }
      }

      public function get_lnd_config() {
        return get_option(WC_LND_NAME . '_lnd_config');
      }

      public function get_lnd_host() {
        $conf = $this->get_lnd_config();
        return 'http' . ($conf['ssl']=='1'?'s':'') . '://' . $conf['host'] . ':' . $conf['port'];
      }

      /**
       * Adds settings page link in plugins section
       * @param  array $links Current links
       * @return array        Returns the merged array
       */
      public function lndwoocommerce_settings_link($links) {
          $plugin_links = [
            '<b><a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=lightning') . '">' . __('Settings', 'lnd-woocommerce') . '</a></b>'
          ];
          return array_merge($links, $plugin_links);
      }

      /**
       * Initialise Gateway Settings Form Fields.
       */
      public function init_form_fields() {
        $this->tickerManager = TickerManager::instance();

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
            'default'     => 'satoshi_tango',
            'options'     => array_map(function($exchange) {
              return $exchange->name;
            }, $this->tickerManager->getValid()),
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
          'description' => array(
            'title'       => __('Customer Message', 'lnd-woocommerce'),
            'type'        => 'textarea',
            'description' => __('Message to explain how the customer will be paying for the purchase.', 'lnd-woocommerce'),
            'default'     => __('You will pay using the Lightning Network.', 'lnd-woocommerce'),
            'desc_tip'    => true,
          ),

        );
      }

      public function create_invoice($order, $ticker) {
        $livePrice = $ticker->rate;

        $invoiceInfo = array();
        $btcPrice = $order->get_total() * ((float)1/ $livePrice);

        $invoiceInfo['value'] = round($btcPrice * 100000000);
        $invoiceInfo['expiry'] = $this->get_option('invoice_expiry');
        $invoiceInfo['memo'] = "Order key: " . $order->get_checkout_order_received_url();

        $invoice = $this->provider->createInvoice($invoiceInfo);
        print_r($invoice);

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
        $order->add_order_note(__('Awaiting payment of', 'lnd-woocommerce') . ' ' .  $invoice->value . ' sats (' . $btcPrice . ')' . " @ 1 BTC ~ " . number_format($ticker->rate, 2) . " " . $ticker->currency . " (+" . $ticker->markup . "% " . __("applied", "lnd-woocommerce") . "). <br> Invoice ID: " . $invoice->payment_request);
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
          wc_add_notice( __('Error: ') . __('Couldn\'t get quote from ticker.', 'lnd-woocommerce'), 'error' );
          return;
        }


        $order->add_order_note(json_encode($ticker)); // Remove
        $invoice = $this->create_invoice($order, $ticker);

        if(property_exists($invoice, 'error')){
          wc_add_notice( __('Error: ') . $invoice->error, 'error' );
          return;
        }

        if(!property_exists($invoice, 'payment_request')) {
          wc_add_notice( __('Error: ') . __('Lightning Node is not reachable at this time. Please contact the store administrator.', 'lnd-woocommerce'), 'error' );
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
        if($this->check_payment($payHash)) {
          $order->payment_complete();
          $order->add_order_note('Lightning Payment received on ' . $callResponse->settle_date);
          status_header(200);
          wp_send_json(true);
        } else {
          status_header(402);
          wp_send_json(false);
        }

      }

      /**
       * Uploads a file, called from ajax
       */
      public function upload_file() {
        switch ($_POST['name']) {
          case 'macaroon':
            $requiredFormat = 'macaroon';
            $destination = WC_LND_MACAROON_FILE;
          break;
          case 'tls':
            $requiredFormat = 'cert';
            $destination = WC_LND_TLS_FILE;
          break;

          default:
            status_header(400);
            wp_send_json(true);
            return;
        }

        $format = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if ($format != $requiredFormat) {
          status_header(415);
          wp_send_json(true);
          return;
        }

        if (move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
          status_header(200);
          wp_send_json(true);
        } else {
          status_header(500);
          wp_send_json(true);
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
       * Generates upload field for Woocommerce init_form_fields
       * @param  string $key
       * @param  array $data
       * @return string
       */
      public function generate_upload_html( $key, $data ) {
    		$defaults = array(
    			'css'               => '',
    			'custom_attributes' => [],
    			'description'       => '',
    			'title'             => 'Upload file',
    			'uploaded'            => false,
    		);
    		$data = wp_parse_args( $data, $defaults );
        $filePath = $key === 'tls' ? WC_LND_TLS_FILE : WC_LND_MACAROON_FILE;

    		ob_start();
    		?>
    		<tr valign="top">
    			<th scope="row" class="titledesc">
    				<label for="<?=esc_attr( $key ) ?>"><?=wp_kses_post( $data['title'] ); ?></label>
    				<?=$this->get_tooltip_html( $data ); ?>
    			</th>
    			<td class="forminp">
    				<fieldset>
    					<legend class="screen-reader-text"><span><?=wp_kses_post( $data['title'] ); ?></span></legend>
              <span <?=$data['uploaded']?'':'class="hidden"'?> id="uploaded_label_<?=$key?>"><b><?=__('Already uploaded', 'lnd-woocommerce') ?></b></span>
              <input type="file" id="<?=esc_attr( $key )?>" name="<?=esc_attr( $key )?>" <?=$this->get_custom_attribute_html( $data ); ?> />
    					<?=$this->get_description_html( $data ); ?>
    				</fieldset>
    			</td>
    		</tr>
    		<?
    		return ob_get_clean();
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

      private function setup_provider() {
        $this->provider = call_user_func([$this, 'set_provider_' . $this->settings['provider']]);
      }

      private function set_provider_lnd() {
        $lnd = LndWrapper::instance();
        // For LND
        if (file_exists(WC_LND_TLS_FILE) && file_exists(WC_LND_MACAROON_FILE)) {
          $lnd->setCredentials ( $this->endpoint, WC_LND_MACAROON_FILE, WC_LND_TLS_FILE);
        } else {
          $this->enabled = 'no';
        }
        return $lnd;
      }

      private function set_provider_lndhub() {
        $lndhub = LndHub::instance();
        $access = get_option(WC_LND_NAME . '_lndhub_config' . '_token'); //TODO: Link with LND_WC_Settings_LNDHUB $prefix
        $settings = get_option(WC_LND_NAME . '_lndhub_config');
        $lndhub->setCredentials(LND_WC_Helpers::generateEndpoint($settings), $settings['userID'], $settings['password']);
        $lndhub->setAccessToken($access);
        $lndhub->setStoreTokenFunc([$this, 'setLndHubToken']);

        return $lndhub;
      }

      //TODO: Doesnt belong here, duplication on LND_WC_Settings_LNDHUB
      public function setLndHubToken($data) {
        update_option(WC_LND_NAME . '_lndhub_config' . '_token', $data); //TODO: Link with LND_WC_Settings_LNDHUB $prefix
        return $data;
      }

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
