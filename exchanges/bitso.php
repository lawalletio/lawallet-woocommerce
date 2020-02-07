<?
class Bitso extends Exchange {
  public $endpoint = 'https://api.bitso.com/v3/';
  public $name = "Bitso";

  public function getPrice($currency='ARS', $crypto='BTC') {
    $content = json_decode($this->request($this->endpoint . "/ticker/?book=" . strtolower($crypto . "_" . $currency)));
    //print_r($content);
    return $content->payload->bid;
  }
}
?>
