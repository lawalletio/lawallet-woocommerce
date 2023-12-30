<?
class ChannelManager {

    protected static $instance = false;
    protected $lndCon = false;
    protected $loopCon = false;

    protected $channels = null;
    protected $outTerms = null;

    public static function instance() {
        if (!static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Make constructor private, so nobody can call "new Class".
     */
    private function __construct() {}

    public function setLND($lndCon) {
      $this->lndCon = $lndCon;
    }

    public function setLoop($loopCon) {
      $this->loopCon = $loopCon;
    }


    public function getChannels() {
      if ($this->channels === null) {
        $this->requireLnd();
        $this->channels = $this->lndCon->listChannels();
      }
      return $this->channels;
    }

    public function getTerms() {
      if ($this->outTerms === null) {
        $this->requireLoop();
        $this->outTerms = $this->loopCon->getOutTerms();
      }
      return $this->outTerms;
    }

    public function getBalance() {
      $channels = $this->getChannels();
      $balance = (object) [
        'local_balance' => 0,
        'remote_balance' => 0,
        'open_channels' => count($channels),
      ];
      foreach ($channels as $channel) {
        $balance->local_balance += $channel->local_balance;
        $balance->remote_balance += $channel->remote_balance;
      };

      return $balance;
    }

    public function swap($amt, $address) {
      $this->requireLoop();
      $terms = $this->getTerms();
      $amt = intval($amt);
      if ($amt > $terms->max_swap_amount) {
        throw new \Exception(__('Amount greater than max_swap_amount terms from Loop', 'lawallet-woocommerce'), 1);
      }

      if ($amt < $terms->min_swap_amount) {
        throw new \Exception(__('Amount lesser than min_swap_amount terms from Loop', 'lawallet-woocommerce'), 1);
      }

      $operation = [
        'amt' => $amt,
        'addr' => $address,
        'sweep_conf_target' => 2,
        'max_swap_fee' => 100000,
        'max_prepay_amt' => 5000,
      ];
      $res = $this->loopCon->loopOut($operation);

    }

    private function requireLnd() {
      if ($this->lndCon === false) {
        throw new \Exception(__('LND server must be set to be able to execute this ChannelManager function', 'lawallet-woocommerce'), 1);
      }
    }

    private function requireLoop() {
      if ($this->loopCon === false) {
        throw new \Exception(__('Loop server must be set to be able to execute this ChannelManager function', 'lawallet-woocommerce'), 1);
      }
    }

}
