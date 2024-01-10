<?php
class Bitex extends Exchange {
  public $endpoint = 'https://bitex.la/api';
  public $name = "Bitex";

  public $fiatList = ['ARS', 'USD'];

  /**
   * Gets current rate
   * @param  string $currency FIAT currency
   * @return float           Rate
   */
  public function getRate($currency='ARS') {
    $content = json_decode($this->request($this->endpoint . "/tickers/" . strtolower("btc_" . $currency)));
    return $content->data->attributes->bid;
  }

  public function setCredentials($data) {
    throw new \Exception(__(printf("setCredentials for %s not implemented", $this->name), "lawallet-woocommerce"), 1);
  }

  public function getPrice($currency='ARS', $crypto='BTC') {
    $content = json_decode($this->request($this->endpoint . "/tickers/" . strtolower($crypto . "_" . $currency)));
    return $content->data->attributes->bid;
  }
}
?>
