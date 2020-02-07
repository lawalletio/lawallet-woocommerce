<?
class SatoshiTango extends Exchange {
  public $endpoint = 'https://apibeta.satoshitango.com/v3';
  public $name = "SatoshiTango";

  public function getPrice($currency='ARS', $crypto='BTC') {
    $content = json_decode($this->request($this->endpoint . "/ticker/$currency/$crypto"));
    return $content->data->ticker->BTC->bid;
  }
}
?>
