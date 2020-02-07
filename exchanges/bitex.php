<?
class Bitex extends Exchange {
  public $endpoint = 'https://bitex.la/api';
  public $name = "Bitex";

  public function getPrice($currency='ARS', $crypto='BTC') {
    $content = json_decode($this->request($this->endpoint . "/tickers/" . strtolower($crypto . "_" . $currency)));
    return $content->data->attributes->bid;
  }
}
?>
