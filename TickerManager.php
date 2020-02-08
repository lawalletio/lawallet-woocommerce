<?
require('exchanges/abstract.php');

require('exchanges/bitcoinaverage.php');
require('exchanges/satoshitango.php');
require('exchanges/ripio.php');
require('exchanges/bitso.php');
require('exchanges/bitex.php');

class TickerManager {

  /**
   * Call this method to get singleton
   */
  public static function instance()
  {
    static $instance = false;
    if( $instance === false )
    {
      // Late static binding (PHP 5.3+)
      $instance = new static();
    }

    return $instance;
  }


  public function __construct() {
    $this->fiat = get_woocommerce_currency();
    $this->currency = 'BTC';

    $this->exchangesList = [
      'satoshi_tango' => new SatoshiTango(),
      'ripio' => new Ripio(),
      'bitso' => new Bitso(),
      'bitex' => new Bitex(),
      'bitcoin_average' => new BitcoinAverage()
    ];

    $this->currentExchange = null;

  }

  public function setExchange($exchangeSlug) {

    if (!array_key_exists($exchangeSlug, $this->exchangesList)) {
      throw new \Exception(sprintf(__( 'Exchange name "%s" not found.', 'lnd-woocommerce' ), $exchangeSlug ), 1);
    }
    $exchange = $this->exchangesList[$exchangeSlug];

    if (!$exchange->hasFiat($this->fiat)) {
      add_action( 'admin_notices', function() use ( $exchange ) {
               $this->unmatchedTicker( $exchange ); } );

      throw new \Exception( __('Can\'t load Exchange.', 'lnd-woocommerce' ), 2);
    }
    $this->currentExchange = $exchange;
  }

  public function unmatchedTicker($exchange) {
    ?>
    <div class="error notice">
      <p><b></n><?=__( 'LND Woocommerce disabled', 'lnd-woocommerce' ) ?></b></p>
      <div>
        <p>
          <? printf( __( 'The current Exchange "%s" doesn\'t have ticker for %s currency.', 'lnd-woocommerce' ), $exchange->name, $this->fiat ) ?>
          <b><a href="./admin.php?page=wc-settings&tab=checkout&section=lightning#woocommerce_lightning_ticker"><?=__('Change Ticker', 'lnd-wocommerce') ?></a></b>
        </p>
        <p>
          <?=__( 'You should select another exchange or change the FIAT currency from Woocommerce.', 'lnd-woocommerce' ) ?>
          <b><a href="./admin.php?page=wc-settings#woocommerce_currency"><?=__('Change Woocommerce Currency', 'lnd-wocommerce') ?></a></b>
        </p>
      </div>
    </div>
    <?php
  }

  public function setCurrency($currency) {
    $this->currency = $currency;
  }

  public function getAll() {
    return $this->exchangesList;
  }

  public function getValid() {
    return array_filter($this->exchangesList, function (Exchange $exchange) {
      return $exchange->hasFiat($this->fiat);
    });
  }

}
