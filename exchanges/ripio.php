<?php
class Ripio extends Exchange {
  public $endpoint = 'https://ripio.com/api/v1';
  public $name = "Ripio";

  public $fiatList = ['ARS'];

  /**
   * Gets current rate
   * @param  string $currency FIAT currency
   * @return float           Rate
   */
  public function getRate($currency='ARS') {
    return 25000000;
  }

  public function getPrice($currency='ARS', $crypto='BTC') {
    $content = json_decode($this->request($this->endpoint . "/rates/"));

    echo "Ripio ticker:\n";
    var_dump($content);
    return $content->rates->{$currency . '_SELL'};
  }
}
?>
