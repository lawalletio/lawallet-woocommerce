<?

class LndWrapper
{

    private $macaroonHex;
    private $endpoint;
    private $coin = 'BTC';
    private $tlsPath;

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
    private function curlWrap( $url, $json, $action, $headers ) {
        $ch			=			curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

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
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $output = curl_exec($ch);

            curl_close($ch);
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
    }

    /**
     * Sets the cryptocurrency to be used
     * @param string $coin Symbol: BTC or LTC
     */
    public function setCoin ( $coin ){
        $this->coin = $coin;
    }

    /**
     * Gets coin's symbol
     * @return string Symbol: BTC or LTC
     */
    public function getCoin () {
        return $this->coin;
    }

    /**
     * Generates QR code image with Google's API
     * @param  string $paymentRequest Invoice payment request
     * @return string                 Remote Image's URL
     */
    public function generateQr( $paymentRequest ){
        $size = "300x300";
        $margin = "0";
        $encoding = "UTF-8";
        return 'https://chart.googleapis.com/chart?cht=qr' . '&chs=' . $size . '&chld=|' . $margin . '&chl=' . $paymentRequest . '&choe=' . $encoding;
    }

    /**
     * Creates invoice
     * @param  array $invoice Invoice data for LND endpoint
     * @return object          Invoice data from LND
     */
    public function createInvoice ( $invoice ) {
        $header = array('Grpc-Metadata-macaroon: ' . $this->macaroonHex , 'Content-type: application/json');
        $createInvoiceResponse = $this->curlWrap( $this->endpoint . '/v1/invoices', json_encode( $invoice ), 'POST', $header );
        $createInvoiceResponse = json_decode($createInvoiceResponse);

        return $createInvoiceResponse;
    }

    /**
     * Gets Invoice from LND api by pay_req
     * @param  string $paymentRequest pay_req field
     * @return object                 Invoice data from LND
     */
    public function getInvoiceInfoFromPayReq ($paymentRequest) {
        $header = array('Grpc-Metadata-macaroon: ' . $this->macaroonHex , 'Content-type: application/json');
        $invoiceInfoResponse = $this->curlWrap( $this->endpoint . '/v1/payreq/' . $paymentRequest,'', "GET", $header );
        $invoiceInfoResponse = json_decode( $invoiceInfoResponse );
        return $invoiceInfoResponse;
    }

    /**
     * Gets Invoice from LND api by Payment hash
     * @param  string $paymentHash base64 of pay_req
     * @return object              Invoice Data
     */
    public function getInvoiceInfoFromHash ( $paymentHash ) {
        $header = array('Grpc-Metadata-macaroon: ' . $this->macaroonHex , 'Content-type: application/json');
        $invoiceInfoResponse = $this->curlWrap( $this->endpoint . '/v1/invoice/' . $paymentHash,'', "GET", $header );
        $invoiceInfoResponse = json_decode( $invoiceInfoResponse );
        return $invoiceInfoResponse;
    }

    /**
     * Generates BTC address from LND
     * @return string BTC address
     */
    public function generateAddress () {
        $header = array('Grpc-Metadata-macaroon: ' . $this->macaroonHex , 'Content-type: application/json');
        $createAddressResponse = $this->curlWrap( $this->endpoint . '/v1/newaddress', null, 'GET', $header );
        $createAddressResponse = json_decode($createAddressResponse);
        return $createAddressResponse->address;
    }
}
