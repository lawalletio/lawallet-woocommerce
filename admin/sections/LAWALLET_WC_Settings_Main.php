<?

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once(WC_LND_PLUGIN_PATH . '/admin/includes/LND_Settings_Page_Generator.php');

if (!class_exists('LAWALLET_WC_Settings_Main')) {

class LAWALLET_WC_Settings_Main extends LND_Settings_Page_Generator {
    public static $prefix = WC_LND_NAME;
    protected static $structure = null;
    protected static $instance = null;
    protected $gateway = null;
    protected $tests = [];

    public function __construct() {
        $this->title = __('LaWallet Main Settings', 'lawallet-woocommerce');
        self::set_structure();
        parent::__construct();
    }


    public function set_gateway($gateway) {
      $this->gateway = $gateway;
    }

    /**
     * Get settings structure
     *
     * @access public
     * @return array
     */
    public static function set_structure() {
        // Define main settings
        static::$structure = array(
            'settings' => array(
                'title' => __('Settings', 'lawallet-woocommerce'),
                'children' => array(
                    'general_settings' => array(
                        'title' => __('Main Settings', 'lawallet-woocommerce'),
                          'children' => [
                            'relay_url' => array(
                              'title'     => __('Relay URL', 'lawallet-woocommerce'),
                              'type'      => 'text',
                              // 'placeholder' => __('Leave blank for new account on BlueWallet server', 'lawallet-woocommerce'),
                              'default'   => 'wss://relay.lawallet.ar',
                              'hint'      => __('The URL for the WSS Nostr Relay', 'lawallet-woocommerce'),
                          ),
                        ],
                    ),
                ),
            ),
            // 'info' => array(
            //     'title' => __('Testing', 'lawallet-woocommerce'),
            //     'template' => 'test',
            //     'children' => [],
            // ),

        );
        return self::$structure;
    }

    public function print_template_dashboard() {
      include WC_LND_ADMIN_PATH . '/views/main/dashboard.php';
    }

    public function print_template_test() {
      $results1 = [
        (object) [
          'title' => 'Testeo de chupachichi',
          'success' => true,
          'message' => 'Muy bien!'
        ],
        (object) [
          'title' => 'Chupala',
          'success' => true,
        ],
        (object) [
          'title' => 'Tercerooo',
          'success' => true,
        ],
        (object) [
          'title' => 'Lallala',
          'success' => false,
        ],
        (object) [
          'title' => 'Lallala',
          'success' => false,
          'message' => 'Mensajiniii'
        ],
      ];

      $results = $this->start_test();
      include WC_LND_ADMIN_PATH . '/views/main/test.php';
    }

    private function test_provider() {
      $providerLabels = [
        'lnd' => 'Lnd Server',
        'lndhub' => 'LndHub',
      ];
      $result = (object) [
        "success" => true,
        "message" => $providerLabels[$this->gateway->settings['provider']],
      ];
      return $result;
    }

    private function test_provider_authenticate() {
      $result = (object) [
        "success" => true,
      ];
      try {
        $this->gateway->provider->authenticate();
      } catch (\Exception $e) {
        $result->success = false;
        $result->message = $e->getMessage();
      }

      return $result;
    }

    private function test_provider_info() {
      $result = (object) [
        "success" => true,
      ];
      try {
        $this->gateway->provider->getInfo();
      } catch (\Exception $e) {
        $result->success = false;
        $result->message = $e->getMessage();
      }
      return $result;
    }

    private function test_exchange() {
      $result = (object) [
        "success" => true,
      ];
      try {
        $exchange = TickerManager::instance()->getExchange();
        $result->message = $exchange->name;
      } catch (\Exception $e) {
        $result->success = false;
        $result->message = $e->getMessage();
      }

      return $result;
    }

    private function test_exchange_rate() {
      $result = (object) [
        "success" => true,
      ];
      try {
        $rate = TickerManager::instance()->getTicker();
        $result->message = $rate->currency . ' ' . $rate->rate;
        $this->tests['ticker'] = $rate;
      } catch (\Exception $e) {
        $result->success = false;
        $result->message = $e->getMessage();
      }
      return $result;
    }

    private function test_invoice_amount() {
      $testAmount = 50;
      $sats = floor($testAmount / $this->tests['ticker']->rate *100000000);
      $this->tests['amt'] = $sats;
      $result = (object) [
        "success" => true,
        "message" => $sats . ' sats'
      ];
      return $result;
    }

    private function test_create_invoice() {
      $result = (object) [
        "success" => true,
      ];
      $invoice = [
        'value' => $this->tests['amt'],
        'memo' => 'LND Test',
      ];

      try {
        $invoice = $this->gateway->provider->createInvoice($invoice);
        $result->message = $invoice->payment_request;
      } catch (\Exception $e) {
        $result->success = false;
        $result->message = $e->getMessage();
      }
      return $result;
    }

    private function start_test() {
      $results = [];
      $tests = [
        'provider' => __("Setting Provider", "lawallet-woocommerce"),
        'provider_authenticate' => __("Authenticating Provider", "lawallet-woocommerce"),
        'provider_info' => __("Getting Provider Info", "lawallet-woocommerce"),
        'exchange' => __("Get Current Exchange", "lawallet-woocommerce"),
        'exchange_rate' => __("Get Ticker Rate", "lawallet-woocommerce"),
        'invoice_amount' => __("Invoice to be created", "lawallet-woocommerce"),
        'create_invoice' => __("Create Invoice", "lawallet-woocommerce"),
      ];

      foreach ($tests as $func => $title) {
        $result = call_user_func([$this, 'test_' . $func]);
        $result->title = $title;
        $results[] = $result;
        //if (isset($result->break) && $result->break && !$result->success) {
        if (!$result->success && !(isset($result->continue) && $result->continue)) {
          break;
        }
      }

      return $results;
    }


}
LAWALLET_WC_Settings_Main::instance();
}
