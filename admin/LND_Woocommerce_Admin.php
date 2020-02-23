<?
define('WC_LND_ADMIN_PATH', WC_LND_PLUGIN_PATH . 'admin');

class LND_Woocommerce_Admin {

  // Singleton instance
  protected static $instance = false;

  /**
   * Singleton control
   */
  public static function get_instance() {
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
    add_action('admin_enqueue_scripts', array($this, 'enqueue_backend_assets'), 20);

  }

  public function enqueue_backend_assets() {
    wp_enqueue_style('admin-css', WC_LND_PLUGIN_URL . '/assets/css/admin.css', array(), WC_LND_VERSION);
  }

  /**
   * top level menu
   */
  public function add_menus() {
      add_submenu_page(
        WC_LND_NAME,
        __('Dashboard', 'lnd-woocommerce'),
        __('Dashboard', 'lnd-woocommerce'),
        'manage_woocommerce',
        WC_LND_NAME,
        [$this, 'dashboard_page']
      );

      // add top level menu page
      add_menu_page(
          'Lightning WC',
          'Lightning WC',
          'manage_options',
          WC_LND_NAME,
          [$this, 'dashboard_page'],
          'dashicons-admin-generic'
      );

      add_submenu_page(
        WC_LND_NAME,
        __( 'LND Server', WC_LND_NAME ),
        __( 'LND Server', WC_LND_NAME ),
        'manage_options',
        WC_LND_NAME .'_lnd_config',
        [$this, 'lnd_config_page']
    );
  }

  function dashboard_page() {
      echo '<div>No Dashboard</div>';
  }

  public function lnd_config_page() {
    $page = LND_WC_Settings_LND::get_instance();
    $page->print_settings_page();
  }
}
