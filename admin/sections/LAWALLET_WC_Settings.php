<?

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once(WC_LND_PLUGIN_PATH . '/admin/includes/LND_Settings_Page_Generator.php');

class LAWALLET_WC_Settings extends LND_Settings_Page_Generator {
    public static $prefix = WC_LND_NAME . '_lndhub_config';
    protected static $structure = null;
    protected static $instance = null;

    protected $notice = null;

    public function __construct() {
        $this->title = __('LaWallet Settings', 'lawallet-woocommerce');

        static::set_structure();
        parent::__construct();

        try {
          $access = $this->getToken();
        } catch (\Exception $e) {
          add_settings_error(
              static::$prefix,
              static::$prefix . '_error',
              $e->getMessage(),
              'error'
          );
        }
    }


    /**
     * Get settings structure
     *
     * @access public
     * @return array
     */
    public static function set_structure() {
        // Define main settings
        static::$structure = [
            'settings' => [
                'title' => __('Config', 'lawallet-woocommerce'),
                'template' => 'config',
                'children' => array(
                    'destination_settings' => array(
                        'title' => __('Destination Config', 'lawallet-woocommerce'),
                        'children' => [
                            'address' => array(
                              'title'     => __('Destination', 'lawallet-woocommerce'),
                              'type'      => 'text',
                              'placeholder' => __('lacrypta@lawallet.ar', 'lawallet-woocommerce'),
                              'required'  => true,
                              'hint'      => __('Should be LUD16 address.', 'lawallet-woocommerce'),
                          ),
                        ],
                    ),
                ),
            ],
            // 'info' => array(
            //     'title' => __('Server Info', 'lawallet-woocommerce'),
            //     'template' => 'info',
            //     'children' => [],
            // ),
            // 'withdraw' => array(
            //     'title' => __('Withdraw', 'lawallet-woocommerce'),
            //     'template' => 'withdraw',
            //     'children' => [],
            // ),
        ];
        return static::$structure;
    }

    public function print_template_dashboard() {
      $ticker = TickerManager::instance()->getTicker();
      include WC_LND_ADMIN_PATH . '/views/lndhub/dashboard.php';
    }

    public function print_template_config() {
      // Get current tab
      $current_tab = static::get_tab();

      include WC_LND_ADMIN_PATH . '/views/lndhub/config.php';

      // Open form container
      echo '<form method="post" action="options.php" enctype="multipart/form-data">';
      // Print settings page content
      include WC_LND_ADMIN_PATH . '/views/fields.php';

      // Close form container
      echo '</form>';
    }

    // public function getEndpoint($settings) {
    //   $protocol = $settings['ssl'] ? 'https' : 'http';
    //   $host = $settings['host'] ? $settings['host'] : '';
    //   $port = $settings['port'] ? $settings['port'] : ($protocol == 'https' ? '443' : '80');
    //   return $protocol . '://' . $host . ':' . $port;
    // }

    private function getToken() {
      return get_option(static::$prefix . '_token');
    }

    public function setToken($data) {
      update_option(static::$prefix . '_token', $data);
      return $data;
    }

    /**
     * Print wp notice for succesful withdrawal
     */
    public function notice_withdraw_success() {
      $amount = $this->notice->payment_route->total_amt;
      $fees = $this->notice->payment_route->total_fees;
      ?>
      <div class="notice notice-success">
        <h3><?=__('Invoice succesfully paid!', 'lawallet-woocommerce')?></h3>
        <p><?=sprintf(__( 'The amount of %s sats has successfully been transferred.', 'lawallet-woocommerce' ), '<b>' . $amount . '</b>'); ?></p>
        <p><?=sprintf(__( 'Total %s sats paid in fees.', 'lawallet-woocommerce' ), '<b>' . $fees . '</b>'); ?></p>
      </div>
      <?
    }

    /**
     * Print wp notice for withdrawal error
     */
    public function notice_withdraw_error() {
      ?>
      <div class="notice notice-error">
        <h3><?=__('Error trying to pay invoice', 'lawallet-woocommerce')?></h3>
        <p><?=$this->notice->getMessage(); ?></p>
      </div>
      <?
    }
    
    /**
     * Handle $_POST withdraw
     */
    public function handle_withdraw() {
      $pay_req = $_POST['pay_req'];
      try {
        $this->notice = $this->lndCon->payInvoice($pay_req);
        add_action( 'admin_notices', [$this, 'notice_withdraw_success'] );
      } catch (\Exception $e) {
        $this->notice = $e;
        add_action( 'admin_notices', [$this, 'notice_withdraw_error'] );
      }
    }
}

LAWALLET_WC_Settings::instance();
