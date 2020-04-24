<?
define('WC_LND_ADMIN_PATH', WC_LND_PLUGIN_PATH . 'admin');

class LND_Woocommerce_Admin {

  // Singleton instance
  protected static $instance = false;
  protected $gateway = null;

  /**
   * Singleton control
   */
  public static function instance() {
      if (!self::$instance) {
          self::$instance = new self();
      }
      return self::$instance;
  }


  public function __construct() {

    /**
     * register our lnd_woocommerce_settings_init to the admin_init action hook
     */
    add_action('admin_init', [$this, 'settings_init']);

    /**
     * register our lnd_woocommerce_options_page to the admin_menu action hook
     */
    add_action('admin_menu', [$this, 'add_menus']);
  }
  /**
   * custom option and settings
   */
  public function settings_init() {

    require_once(WC_LND_PLUGIN_PATH . '/admin/sections/LND_WC_Settings_LND.php');
    require_once(WC_LND_PLUGIN_PATH . '/admin/sections/LND_WC_Settings_Loop.php');
    require_once(WC_LND_PLUGIN_PATH . '/admin/sections/LND_WC_Settings_LNDHUB.php');
    require_once(WC_LND_PLUGIN_PATH . '/admin/sections/LND_WC_Settings_Main.php');
    add_action('admin_enqueue_scripts', [$this, 'enqueue_backend_assets'], 20);

  }

  public function set_gateway($gateway) {
    $this->gateway = $gateway;
  }

  public function enqueue_backend_assets() {
    // Styles
    wp_enqueue_style('admin-css', WC_LND_PLUGIN_URL . '/assets/css/admin.css', array(), WC_LND_VERSION);

    // Scripts
    wp_enqueue_script('admin-script', WC_LND_PLUGIN_URL . '/assets/js/admin.js',[], WC_LND_VERSION);
    wp_enqueue_script('bolt11-script', WC_LND_PLUGIN_URL . '/assets/js/bolt11.js',[], WC_LND_VERSION);
  }

  /**
   * top level menu
   */
  public function add_menus() {
      add_submenu_page(
        WC_LND_NAME,
        __('Settings', 'lnd-woocommerce'),
        __('Settings', 'lnd-woocommerce'),
        'manage_woocommerce',
        WC_LND_NAME,
        [$this, 'lnd_config_main']
      );

      // add top level menu page
      add_menu_page(
          'Lightning WC',
          'Lightning WC',
          'manage_options',
          WC_LND_NAME,
          [$this, 'lnd_config_main'],
          'dashicons-admin-generic'
      );

      add_submenu_page(
        WC_LND_NAME,
        __( 'LND Server', 'lnd-woocommerce' ),
        __( 'LND Server', 'lnd-woocommerce' ),
        'manage_options',
        WC_LND_NAME .'_lnd_config',
        [$this, 'lnd_config_page']
      );

      add_submenu_page(
        WC_LND_NAME,
        __( 'Loop Server', 'lnd-woocommerce' ),
        __( 'Loop Server', 'lnd-woocommerce' ),
        'manage_options',
        WC_LND_NAME .'_loop_config',
        [$this, 'loop_config_page']
      );

      add_submenu_page(
        WC_LND_NAME,
        __( 'LndHub', 'lnd-woocommerce' ),
        __( 'LndHub', 'lnd-woocommerce' ),
        'manage_options',
        WC_LND_NAME .'_lndhub_config',
        [$this, 'lndhub_config_page']
      );
  }

  public function lnd_config_main() {
    $page = LND_WC_Settings_Main::instance();
    $page->set_gateway($this->gateway);
    $page->print_settings_page();
  }

  public function lnd_config_page() {
    $page = LND_WC_Settings_LND::instance();
    $page->print_settings_page();
  }

  public function loop_config_page() {
    $page = LND_WC_Settings_Loop::instance();
    $page->print_settings_page();
  }

  public function lndhub_config_page() {
    $page = LND_WC_Settings_LNDHUB::instance();
    $page->print_settings_page();
  }
}
