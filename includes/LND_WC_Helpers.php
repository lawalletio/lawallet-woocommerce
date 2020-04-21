<?
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

class LND_WC_Helpers {
  public static function generateEndpoint($settings) {
    $protocol = $settings['ssl'] ? 'https' : 'http';
    $host = $settings['host'] ? $settings['host'] : '';
    $port = $settings['port'] ? $settings['port'] : ($protocol == 'https' ? '443' : '80');
    return $protocol . '://' . $host . ':' . $port;
  }
}
?>
