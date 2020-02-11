<?
require('Exchange.php');

require(__DIR__ . '/../exchanges/bitcoinaverage.php');
require(__DIR__ . '/../exchanges/satoshitango.php');
require(__DIR__ . '/../exchanges/ripio.php');
require(__DIR__ . '/../exchanges/bitso.php');
require(__DIR__ . '/../exchanges/bitex.php');

class TickerManager {
  /**
   * Call this method to get singleton
   */
  public static function instance() {
    static $instance = false;
    if( $instance === false ) {
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

  /**
   * Sets exchange to be used
   * @param string $exchangeSlug Lower case with underscore for names
   */
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

  /**
   * Error creator when fiat is not supported by the currenct exchange
   * @param  object $exchange Exchange object
   */
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
    <?
  }

  /**
   * Sets cryptocurrency
   * @param string $currency Symbol
   */
  public function setCurrency($currency) {
    $this->currency = $currency;
  }

  /**
   * Gets full exchange list
   * @return array Array of Exchange objects
   */
  public function getAll() {
    return $this->exchangesList;
  }

  /**
   * Get valid exchanges for the fiat currency selected
   * @return array Array of Exchange objects
   */
  public function getValid() {
    return array_filter($this->exchangesList, function (Exchange $exchange) {
      return $exchange->hasFiat($this->fiat);
    });
  }

  /**
   * Get ticker from ARS Exchanges
   * @return float Price
   */
  public function getTicker($markup=0) {
    $rate = $this->currentExchange->getRate();
    if ($markup <> 0) {
      $markup = (float) $markup;
      $rate = $rate/(1+$markup/100);
    }

    return (object) array(
      'currency' => $this->fiat,
      'rate' => $rate,
      'markup' => $markup
    );
  }

}
