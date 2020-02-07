<?
class SatoshiTango extends Exchange {
  public $endpoint = 'https://apibeta.satoshitango.com/v3';
  public $name = "SatoshiTango";

  protected $fiatList = ['ARS', 'USD'];

  public function getRate($currency='ARS') {
    $content = json_decode($this->request($this->endpoint . "/ticker/$currency/BTC"));
    return $content->data->ticker->BTC->bid;
  }
}
?>
