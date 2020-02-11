<?
class BitcoinAverage extends Exchange {
  public $endpoint = 'https://ripio.com/api/v1';
  public $name = "Bitcoin Average";

  public $fiatList = ['USD'];

  /**
   * Gets current rate
   * @param  string $currency FIAT currency
   * @return float           Rate
   */
  public function getRate($currency='ARS') {
    $content = json_decode($this->request($this->endpoint . "/rates/"));
    return $content->rates->{$currency . '_SELL'};

    $ticker = 'BTC' . $currency;
    $tickerUrl = "https://apiv2.bitcoinaverage.com/indices/global/ticker/" . $ticker;
    $aHTTP = array(
      'http' =>
        array(
        'method'  => 'GET',
          )
    );
    $content = file_get_contents($tickerUrl, false);
    $result = json_decode($content, true)['ask'];
    if (is_numeric($result)) {
      throw new \Exception($content, 1);
    }
    return $result;
  }
}
?>
