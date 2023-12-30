<?

class LndHub {

    private $userID;
    private $password;
    private $accessToken;
    private $tokenExpiry;
    private $defaultTokenTTL = 86400;
    private $endpoint;
    private $headers = [];
    private $updateTokenFunc = null;

    protected static $instance = false;

    public static function instance() {
        if (!static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Make constructor private, so nobody can call "new Class".
     */
    private function __construct() {
    }

    /**
     * Make clone magic method private, so nobody can clone instance.
     */
    private function __clone() {}

    /**
     * Make sleep magic method private, so nobody can serialize instance.
     */
    private function __sleep() {}

    /**
     * Make wakeup magic method private, so nobody can unserialize instance.
     */
    private function __wakeup() {}

    /**
     * Custom function to make curl requests
     */
    private function curlWrap( $url, $action='GET', $json=[] ) {
        $ch	=	curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->endpoint . $url);

        switch($action){
            case "POST":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, gettype($json) === 'string' ? $json : http_build_query($json));
                break;
            case "GET":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                break;
            case "PUT":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                break;
            case "DELETE":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            default:
                break;
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            //This is set to 0 for development mode. Set 1 when production (self-signed certificate error)
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
            curl_setopt($ch, CURLOPT_VERBOSE, true);

            $verbose = fopen('php://memory', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose);

            $output = json_decode(curl_exec($ch));
            curl_close($ch);

            $this->checkErrors($verbose, $output);
            //die();
            return $output;
    }

    /**
     * Sets LND credentials
     * @param string $endpoint     LND URL
     * @param string $macaroonPath Full local path for macaroon file
     * @param string $tlsPath      Full local path for tls certificate file
     */
    public function setCredentials ($endpoint, $userID=null, $password=null) {
        $this->endpoint = $endpoint;
        $this->userID = $userID;
        $this->password = $password;
    }

    /**
     * Sets the callback for storing the access token
     * @param callable $callback Callback function
     */
    public function setStoreTokenFunc($callback) {
      $this->updateTokenFunc = $callback;
    }

    public function getAccessToken() {
      return (object) [
        'token' => $this->accessToken,
        'expiry' => $this->tokenExpiry,
      ];
    }

    public function setAccessToken($token=null, $store=false) {

      $accessToken = $token ?: $this->getEmptyToken();
      $this->accessToken = $accessToken->token;
      $this->tokenExpiry = $accessToken->expiry;

      $this->headers =  ['Authorization: Bearer ' . $this->accessToken];

      $token = $this->getAccessToken();
      if ($store) {
        call_user_func($this->updateTokenFunc, $token); // Update database
      }
      return $token;
    }

    public function login() {
        if ($this->userID === null || $this->password === null) {
          throw new \Exception(__("Unable to login. UserID or password missing", "lawallet-woocommerce"), 1);
        }
        $data = (object)[
          "login" => $this->userID,
          "password" => $this->password
        ];
        $response = $this->curlWrap('/auth?type=auth', 'POST', $data);
        //print_r($response);
        $token = (object) [
          'token' => $response->access_token,
          'expiry' => time() + $this->defaultTokenTTL,
        ];
        return $this->setAccessToken($token, true);
    }

    public function authenticate() {
      if ($this->accessToken === null || !$this->tokenExpiry || time() - $this->tokenExpiry - $this->defaultTokenTTL <= 0) {
        return $this->login();
      }
      return $this->getAccessToken();
    }

    public function createUser() {
      $data = [
      	"partnerid" => "bluewallet",
      	"accounttype" => "test"
      ];
      $response = $this->curlWrap('/create', 'POST', $data);
      $this->userID = $response->login;
      $this->password = $response->password;
      return (object) [
        'userID' => $response->login,
        'password' => $response->password,
      ];
    }

    /**
     * Get Info
     * @param  array $invoice Invoice data for LND endpoint
     * @return object          Invoice data from LND
     */
    public function getInfo() {
        $response = $this->curlWrap('/getinfo');

        return $response;
    }

    /**
     * Creates invoice
     * @param  array $invoice Invoice data for LND endpoint
     * @return object          Invoice data from LND
     */
    public function createInvoice($invoice) {
      $data = (object) [
        'amt' => $invoice['value'],
        'memo' => $invoice['memo'],
        //'expiry' => $invoice['expiry'], // TODO: Lndhub compatibility
      ];
      $response = $this->curlWrap('/addinvoice', 'POST', $data);

      $response->payment_hash = $this->parseRHash($response->r_hash);

      $response->expiry = time() + 3600; // Default 1 hour
      return $response;
    }

    public function payInvoice($pay_req) {
        $invoice = (object) ["invoice" => $pay_req];
        $response = $this->curlWrap('/payinvoice', 'POST', $invoice);
        return $response;
    }

    /**
     * Gets Invoice from LND api by pay_req
     * @param  string $paymentRequest pay_req field
     * @return object                 Invoice data from LND
     */
    public function getInvoiceInfoFromPayReq($paymentRequest) {
        $invoiceInfoResponse = $this->curlWrap('/decodeinvoice?invoice=' . $paymentRequest);
        return $invoiceInfoResponse;
    }

    /**
     * Generates BTC address from LND
     * @return string BTC address
     */
    public function generateAddress() {
        $address = $this->curlWrap('/getbtc');
        return $address;
    }

    public function getBalance() {
        $response = $this->curlWrap('/balance');
        return $response->BTC->AvailableBalance;
    }

    public function getPendingTransactions() {
        $response = $this->curlWrap('/getpending');
        return $response;
    }

    public function checkPayment($paymentHash) {
        $response = $this->curlWrap('/checkpayment/' . $paymentHash);
        return $response->paid;
    }

    private function checkErrors($verbose, $output) {
      rewind($verbose);
      $verboseLog = stream_get_contents($verbose);

      if (strpos($verboseLog, 'HTTP/1.1 429')) {
        throw new \Exception(__('Too many requests on Lndhub', 'lawallet-woocommerce'), 429);
      }
      if (strpos($verboseLog, 'HTTP/1.1 200 OK') === false) {
        throw new \Exception(__('Server is unreacheable, must be down or wrong port is given', 'lawallet-woocommerce'), 501);
      }

      $errors = [
        1 => __("Bad Authentication", "lawallet-woocommerce"),
        8 => __("Bad Arguments", "lawallet-woocommerce"),
      ];

      if (isset($output->error) && $output->error) {
        if ($output->code = 1) {
          $this->setAccessToken(null, true);
        }
        throw new \Exception($errors[$output->code], $output->code);
      }

      return true;

    }

    private function getEmptyToken() {
      return (object) [
        'token' => null,
        'expiry' => 0
      ];
    }

    private function parseRHash($rhash) {
      if (gettype($rhash) === 'string') {
        return bin2hex(base64_decode($rhash));
      }
      return implode(array_map(function ($v) {
        return str_pad(dechex($v), 2, "00", STR_PAD_LEFT);
      }, $rhash->data));
    }
}
