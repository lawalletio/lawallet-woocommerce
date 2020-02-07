<?
class Bitex extends Exchange {
  public $endpoint = 'https://bitex.la/api';
  public $name = "Bitex";

  public $fiatList = ['ARS', 'USD'];

  public function getRate($currency='ARS') {
    $content = json_decode($this->request($this->endpoint . "/tickers/" . strtolower("btc_" . $currency)));
    return $content->data->attributes->bid;
  }
}
?>
