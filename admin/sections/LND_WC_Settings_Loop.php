<?

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once(WC_LND_PLUGIN_PATH . '/admin/includes/LND_Settings_Page_Generator.php');
require_once(WC_LND_PLUGIN_PATH . '/includes/LoopWrapper.php');

if (!class_exists('Loop_WC_Settings_Loop')) {

class LND_WC_Settings_Loop extends LND_Settings_Page_Generator {
    public static $prefix = WC_LND_NAME . '_loop_config';

    public function __construct() {
        $this->title = __('Loop Settings', 'lnd-woocommerce');

        self::set_structure();
        parent::__construct();

        $this->loopCon = LoopWrapper::instance();
        $this->loopCon->setEndpoint('http://' . $this->settings['host'] . ':' . $this->settings['port']);
    }


    /**
     * Get settings structure
     *
     * @access public
     * @return array
     */
    public static function set_structure() {
        // Define main settings
        self::$structure = array(
            'settings' => array(
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
                                'hint'      => __('Loop port, must be the same as <b>restlisten</b> value from lnd.conf. Please type just the port number.', 'lnd-woocommerce'),
                            ),
                        ],
                    ),
                ),
            ),
            'info' => array(
                'title' => __('Server Info', 'lnd-woocommerce'),
                'template' => 'info',
                'children' => [
                  'general_settings' => [
                      'title' => __('General', 'lnd-woocommerce'),
                      'children' => [
                          'empty' => array(
                              'title'     => __('Test', 'lnd-woocommerce'),
                              'type'      => 'template',
                              'view'      => 'footer',
                          ),
                      ],
                  ],
                ],
            ),
        );
        return self::$structure;
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

}
LND_WC_Settings_Loop::instance();
}
