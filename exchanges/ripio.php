<?
class Ripio extends Exchange {
  public $endpoint = 'https://ripio.com/api/v1';
  public $name = "Ripio";

  public $fiatList = ['ARS'];

  public function getRate($currency='ARS') {
    $content = json_decode($this->request($this->endpoint . "/rates/"));
    return $content->rates->{$currency . '_SELL'};
  }
}
?>
