<?php
class Yadio extends Exchange {
  public $endpoint = 'https://api.yadio.io/exrates/btc';
  public $name = "Yadio";
  private $apiKey = "";

  protected $fiatList = ['ARS', 'USD'];

  /**
   * Gets current rate
   * @param  string $currency FIAT currency
   * @return float           Rate
   */
  public function getRate($currency='ARS') {
    $content = json_decode($this->request($this->endpoint ));
    $list = $content->BTC;
    if (!$list) {
      throw new Exception("BTC object missing on response", 1);
    }
    return $list->{$currency};
  }

  private function generateHeaders() {
    return [
      "Content-Type: application/json",
      "cache-control: no-cache"
    ];
  }

  public function setCredentials($data) {
    return;
  }

  public function quote($amount) {
    $data = (object) [
      "currencyfrom" => "BTC",
      "amountfrom" => $amount,
      "currencyto" => $currency,
      "amountto" => 25
    ];
    return json_decode($this->request($this->endpoint . "/sellcrypto/quote"), $data);

    // {
    //   "code": "success",
    //   "opid": 12345,
    //   "amount": 0.00202,
    //   "subtotal": 0.0020402,
    //   "typefee": 0.1,
    //   "fee": 0.0000202,
    //   "crypto": "BTC",
    //   "exchangerate": 8756,
    //   "exchangerateunit": "btc/usd"
    // }
  }

  public function sell($amount, $currency='ARS') {
    trigger_error(__('sell not implemented for this class yet'), E_USER_WARNING);
  }
}
?>
