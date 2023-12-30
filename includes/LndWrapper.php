<?

class LndWrapper {

    private $macaroonHex;
    private $endpoint;
    private $tlsPath;
    private $headers = [];

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
            curl_setopt($ch, CURLOPT_CAINFO, $this->tlsPath);
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
    public function setCredentials ( $endpoint , $macaroonPath , $tlsPath){
        $this->endpoint = $endpoint;
        $this->macaroonHex = bin2hex(file_get_contents($macaroonPath));
        $this->tlsPath = $tlsPath;

        $this->headers = ['Grpc-Metadata-macaroon: ' . $this->macaroonHex , 'Content-type: application/json'];
    }

    public function authenticate() {
      return true;
    }

    /**
     * Get Info
     * @param  array $invoice Invoice data for LND endpoint
     * @return object          Invoice data from LND
     */
    public function getInfo() {
        $response = $this->curlWrap('/v1/getinfo');

        return $response;
    }

    /**
     * Creates invoice
     * @param  array $invoice Invoice data for LND endpoint
     * @return object          Invoice data from LND
     */
    public function createInvoice($invoice) {
        $response = $this->curlWrap('/v1/invoices', 'POST', json_encode( $invoice ));
        $response->payment_hash = bin2hex(base64_decode($response->r_hash));
        $response->expiry = time() + $invoice['expiry'];
        return $response;
    }

    public function payInvoice($pay_req) {
        $invoice = (object) ["payment_request" => $pay_req];
        $response = $this->curlWrap('/v1/channels/transactions', 'POST', json_encode( $invoice ));

        if ($response->payment_error !== "") {
          if ($response->payment_error === 'invoice is already paid') {
            $response->payment_error = __('Invoice is already paid', 'lawallet-woocommerce');
          }
          throw new \Exception($response->payment_error, 1);
        }
        return $response;
    }

    public function checkPayment($paymentHash) {
      $invoice = $this->getInvoiceInfoFromHash($paymentHash);
      if(!$invoice) {
        throw new \Exception(__('Invoice not found', 'lawallet-woocommerce'), 404);
      }

      return property_exists($invoice, 'settled') && $invoice->settled;
    }

    /**
     * Gets Invoice from LND api by pay_req
     * @param  string $paymentRequest pay_req field
     * @return object                 Invoice data from LND
     */
    public function getInvoiceInfoFromPayReq($paymentRequest) {
        $invoiceInfoResponse = $this->curlWrap('/v1/payreq/' . $paymentRequest);
        return $invoiceInfoResponse;
    }

    /**
     * Gets Invoice from LND api by Payment hash
     * @param  string $paymentHash base64 of pay_req
     * @return object              Invoice Data
     */
    public function getInvoiceInfoFromHash($paymentHash) {
        $invoiceInfoResponse = $this->curlWrap('/v1/invoice/' . $paymentHash);
        return $invoiceInfoResponse;
    }

    /**
     * Generates BTC address from LND
     * @return string BTC address
     */
    public function generateAddress() {
        $createAddressResponse = $this->curlWrap('/v1/newaddress');
        return $createAddressResponse->address;
    }

    public function listChannels() {
        $response = $this->curlWrap('/v1/channels');
        return $response->channels;
    }

    private function checkErrors($verbose, $output) {
      rewind($verbose);
      $verboseLog = stream_get_contents($verbose);

      // print_r($output);
      // print_r($verboseLog);
      if (strpos($verboseLog, 'HTTP/1.1 200 OK') !== false) {

        return true;
      }

      $errors = [
        ["Failed to connect to", __("Server is unreacheable, must be down or wrong port is given", "lawallet-woocommerce"), 1],
        ["Recv failure: Connection reset by peer", __("Server reached but is not a LND server or you've set an invalid port", "lawallet-woocommerce"), 1],
        ["HTTP/1.0 400 Bad Request", __("Server reached but is not a Loop server or invalid port (must be restlisten)", "lawallet-woocommerce"), 400],
        ["HTTP/1.1 404 Not Found", __("Got a 404 response from server, please check if lnd wallet is created and already unlocked", "lawallet-woocommerce"), 404],
        ["Empty reply from server", __("Server reached but is not LND restlisten port. Looks like rpcport", "lawallet-woocommerce"), 1],
        ["Connection reset by peer", __("Server reached but is not LND restlisten port", "lawallet-woocommerce"), 1],
      ];

      foreach ($errors as $value) {
        if (strpos($verboseLog, $value[0]) !== false) {
          throw new \Exception($value[1], $value[2]);
        }
      }

      $messages = [
        ["signature mismatch after caveat verification", __("Signature mismatch tls.cert not accepted on server", "lawallet-woocommerce")],
        ["permission denied", __("Permission denied from LND server, please check your macaroon file", "lawallet-woocommerce")],
        ["invoice expired", __("Invoice expired", "lawallet-woocommerce")],
        ["payment hash must be exactly", __("Invalid Payment Hash format", "lawallet-woocommerce")],
      ];

      foreach ($messages as $value) {
        if (strpos($output->message, $value[0]) !== false) {
          throw new \Exception($value[1], 1);
        }
      }

      throw new \Exception('Unknown error', 1);
    }
}
