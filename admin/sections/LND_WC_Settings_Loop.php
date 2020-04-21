<?

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once(WC_LND_PLUGIN_PATH . '/admin/includes/LND_Settings_Page_Generator.php');
require_once(WC_LND_PLUGIN_PATH . '/includes/LoopWrapper.php');

class LND_WC_Settings_Loop extends LND_Settings_Page_Generator {
    public static $prefix = WC_LND_NAME . '_loop_config';
    protected static $structure = null;
    protected static $instance = null;

    protected $lndCon = false;
    protected $loopCon = false;

    public function __construct() {
        $this->title = __('Loop Settings', 'lnd-woocommerce');

        static::set_structure();
        parent::__construct();

        $this->lndCon = LndWrapper::instance();
        $this->loopCon = LoopWrapper::instance();
        $this->loopCon->setEndpoint('http://' . $this->settings['host'] . ':' . $this->settings['port']);

        $this->channelManager = ChannelManager::instance();
        $this->channelManager->setLND($this->lndCon);
        $this->channelManager->setLoop($this->loopCon);

        if (isset($_POST['method'])) {
          if ($_POST['method'] == 'loop_out') {
            $this->loop_out($_POST);
          }
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
            'dashboard' => [
                'title' => __('Dashboard', 'lnd-woocommerce'),
                'template' => 'dashboard',
                'children' => [],
            ],
            'settings' => [
                'title' => __('Config', 'lnd-woocommerce'),
                'children' => array(
                    'general_settings' => array(
                        'title' => __('Server Config', 'lnd-woocommerce'),
                        'children' => [
                            'host' => array(
                                'title'     => __('Host', 'lnd-woocommerce'),
                                'type'      => 'text',
                                'default'   => __('localhost', 'lnd-woocommerce'),
                                'required'  => true,
                                'hint'      => __('Loop host address, you can use <b>localhost</b>.', 'lnd-woocommerce'),
                            ),
                            'port' => array(
                                'title'     => __('Port', 'lnd-woocommerce'),
                                'type'      => 'text',
                                'default'   => 8081,
                                'required'  => true,
                                'hint'      => __('Loop port, must be the same as <b>restlisten</b>. Please type just the port number.', 'lnd-woocommerce'),
                            ),
                        ],
                    ),
                ),
            ],
            'info' => array(
                'title' => __('Server Info', 'lnd-woocommerce'),
                'template' => 'info',
                'children' => [],
            ),
        ];
        return static::$structure;
    }

    public function print_template_info() {
      try {
        $info = $this->loopCon->getSwapsList();
      } catch (\Exception $e) {
        $message = $e->getMessage();
        // Print settings error content
        include WC_LND_ADMIN_PATH . '/views/error.php';
        return;
      }

      // Print settings page content
      include WC_LND_ADMIN_PATH . '/views/loop/info.php';
    }

    public function print_template_dashboard() {
      $ticker = TickerManager::instance()->getTicker();
      try {
        $balance = $this->channelManager->getBalance();
        $channels = $this->channelManager->getChannels();
        $terms = $this->channelManager->getTerms();
      } catch (\Exception $e) {
        $message = $e->getMessage();
        // Print settings error content
        include WC_LND_ADMIN_PATH . '/views/error.php';
        return;
      }

      $total = $balance->local_balance+$balance->remote_balance;
      $ratio = number_format($balance->remote_balance / $total, 4);

      include WC_LND_ADMIN_PATH . '/views/loop/dashboard.php';
    }

    private function loop_out($data) {
      try {
        echo 'Looping...';
        $this->channelManager->swap($data['amt'], $data['address']);
      } catch (\Exception $e) {
        print_r($e);
        die();
      }
    }
}

LND_WC_Settings_Loop::instance();
