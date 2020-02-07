<?
class Bitso extends Exchange {
  public $endpoint = 'https://api.bitso.com/v3/';
  public $name = "Bitso";

  public $fiatList = ['ARS'];

  public function getRate($currency='ARS') {
    $content = json_decode($this->request($this->endpoint . "/ticker/?book=" . strtolower("btc_" . $currency)));
    return $content->payload->bid;
  }
}
?>
