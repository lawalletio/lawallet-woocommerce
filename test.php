<?
echo "Testing de Exchanges :";

require('exchanges/abstract.php');

require('exchanges/satoshitango.php');
require('exchanges/ripio.php');
require('exchanges/bitso.php');
require('exchanges/bitex.php');

$exchangesList = [
  'satoshi_tango' => new SatoshiTango(),
  'ripio' => new Ripio(),
  'bitso' => new Bitso(),
  'bitex' => new Bitex(),
];

print_r(array_map(function($exchange) {
  return $exchange->name;
}, $exchangesList));

foreach ($exchangesList as $exchange) {
?>
<div>
  <b><?=$exchange->name?></b> : <?=$exchange->getPrice()?>
</div>
<?
}
?>
