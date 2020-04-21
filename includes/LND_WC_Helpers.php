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

  public static function convertSats($sats, $ticker) {
    return $ticker->currency . ' ' . rtrim(rtrim(number_format($sats/100000000 * $ticker->rate, 2), '0'), '.');
  }
}
?>
