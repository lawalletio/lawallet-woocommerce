<?
class Bitso extends Exchange {
  public $endpoint = 'https://api.bitso.com/v3/';
  public $name = "Bitso";

<<<<<<< HEAD
  public $fiatList = ['ARS'];

  /**
   * Gets current rate
   * @param  string $currency FIAT currency
   * @return float           Rate
   */
  public function getRate($currency='ARS') {
    $content = json_decode($this->request($this->endpoint . "/ticker/?book=" . strtolower("btc_" . $currency)));
=======
  public function getPrice($currency='ARS', $crypto='BTC') {
    $content = json_decode($this->request($this->endpoint . "/ticker/?book=" . strtolower($crypto . "_" . $currency)));
    //print_r($content);
>>>>>>> Minimal changes
    return $content->payload->bid;
  }
}
?>
