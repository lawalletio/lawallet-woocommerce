<?

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once(WC_LND_PLUGIN_PATH . '/admin/includes/LND_Settings_Page_Generator.php');

if (!class_exists('LND_WC_Settings_Main')) {

class LND_WC_Settings_Main extends LND_Settings_Page_Generator {
    public static $prefix = WC_LND_NAME;
    protected static $structure = null;
    protected static $instance = null;

    public function __construct() {
        $this->title = __('LND Main Settings', 'lnd-woocommerce');
        self::set_structure();
        parent::__construct();
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
                'title' => __('Settings', 'lnd-woocommerce'),
                'children' => array(
                    'general_settings' => array(
                        'title' => __('Main Settings', 'lnd-woocommerce'),
                        'children' => [
                            'provider' => array(
                                'title'     => __('LND Provider', 'lnd-woocommerce'),
                                'type'      => 'select',
                                'default'   => 'lnd',
                                'options'   => [
                                  'lnd' => 'LND Server',
                                  'lndhub' => 'LndHub',
                                ],
                                'hint'      => __('Lnd server to be used.', 'lnd-woocommerce'),
                            )
                        ],
                    ),
                ),
            ),
        );
        return self::$structure;
    }

    public function print_template_dashboard() {
      include WC_LND_ADMIN_PATH . '/views/main/dashboard.php';
    }

}
LND_WC_Settings_Main::instance();
}
