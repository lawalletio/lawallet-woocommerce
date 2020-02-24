<?

class LoopWrapper {

    private $endpoint = 'http://lnd.coinmelon.com:11010';

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

    public function setEndpoint($endpoint) {
      $this->endpoint = $endpoint;
    }

    /**
     * Custom function to make curl requests
     */
    private function curlWrap( $url, $action='GET', $json=[] ) {
        $ch	=	curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->endpoint . $url);

        switch($action){
            case "POST":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
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
            //curl_setopt($ch, CURLOPT_CAINFO, $this->tlsPath);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
            curl_setopt($ch, CURLOPT_VERBOSE, true);

            $verbose = fopen('php://memory', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose);

            $output = json_decode(curl_exec($ch));
            curl_close($ch);

            $this->checkErrors($verbose);
            return $output;
    }

    /**
     * [getSwapsList description]
     * @return array Array of swaps
     */
    public function getSwapsList() {
        $response = $this->curlWrap('/v1/loop/swaps');
        return $response->swaps;
    }

    /**
     * Creates invoice
     * @param  array $invoice Invoice data for LND endpoint
     * @return object          Invoice data from LND
     */
    public function testPost($invoice) {
        $createInvoiceResponse = $this->curlWrap('/v1/invoices', json_encode( $invoice ), 'POST');

        return $createInvoiceResponse;
    }

    private function checkErrors($verbose) {
      rewind($verbose);
      $verboseLog = stream_get_contents($verbose);

      if (strpos($verboseLog, 'HTTP/1.1 200 OK') !== false) {
        return true;
      }

      $errors = [
        ["Failed to connect to", __("Server is unreacheable, must be down or wrong port is given", "lnd-woocommerce")],
        ["Recv failure: Connection reset by peer", __("Server reached but is not a loop server or you've set an invalid port", "lnd-woocommerce")],
        ["HTTP/1.0 400 Bad Request", __("Server reached but is not a Loop server or invalid port (must be restlisten)", "lnd-woocommerce")],
        ["Empty reply from server", __("Server reached but is not Loop restlisten port. Looks like rpcport", "lnd-woocommerce")],
        ["Connection reset by peer", __("Server reached but is not Loop restlisten port. Looks like LND listen or restport", "lnd-woocommerce")],
      ];

      foreach ($errors as $value) {
        if (strpos($verboseLog, $value[0]) !== false) {
          throw new \Exception($value[1], 1);
        }
      }

      throw new \Exception('Unknown error', 1);
    }
}
