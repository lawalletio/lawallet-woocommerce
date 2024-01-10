<?php
class SatoshiTango extends Exchange {
  public $endpoint = 'https://api.satoshitango.com/v3';
  public $name = "SatoshiTango";
  private $apiKey = "";

  protected $fiatList = ['ARS', 'USD'];

  /**
   * Gets current rate
   * @param  string $currency FIAT currency
   * @return float           Rate
   */
  public function getRate($currency='ARS') {
    $content = json_decode($this->request($this->endpoint . "/ticker/$currency/BTC"));
    return $content->data->ticker->BTC->bid;
  }

  private function generateHeaders() {
    return [
      "Content-Type: application/json",
      "Authorization: Bearer " . $this->apiKey,
      "cache-control: no-cache"
    ];
  }

  public function setCredentials($data) {
    $this->apiKey = $data->apiKey;
    $this->addHeaders(generateHeaders());
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
    $quote = $this->quote($amount, $currency);
    $data = (object) [
      "opid" => $quote->opid
    ];
    $content = json_decode($this->request($this->endpoint . "/sellcrypto/exec"), $data);
  }
}
?>
