<?php
class LUD16 {

    const $RELAY_URL = "wss://nos.lol";
    protected $address;
    protected $callbackUrl;
    protected $allowsNostr;
    protected $nostrPubkey;
    protected $federationId;
    protected $accountPubKey;
    protected $minSendable;
    protected $maxSendable;

    protected $loaded = false;

    // constructor
    public function __construct() {
    }

    public static function fromAddress(string $address) {
        $instance = new self();

        $url = LUD16::generateUrl($address);
        $data = file_get_contents($url);

        $json = json_decode($data, true);
        $json["address"] = $address;

        if (!isset($json['allowsNostr']) || !$json['allowsNostr']) {
            throw new Exception("allowsNostr is not set to true in LUD16", 1);
        }

        if (!isset($json['nostrPubkey'])) {
            throw new Exception("nostrPubkey not found in LUD16", 1);
        }

        if (!isset($json['callbackUrl'])) {
            throw new Exception("callback not found in LUD16", 1);
        }

        $instance->fill( $json );
        return $instance;
    }

    public function fromJSON(stdClass $json) {
        $this->address = $json['address'];
        $this->callbackUrl = $json['callback'];
        $this->nostrPubkey = $json['nostrPubkey'];
        $this->allowsNostr = $json['allowsNostr'];
        $this->federationId = $json['federationId'];
        $this->accountPubKey = $json['accountPubKey'];
        $this->minSendable = $json['minSendable'];
        $this->maxSendable = $json['maxSendable'];

        $this->loaded = true;
    }

    public function createInvoice(int $amount) {
        if (!$this->loaded) {
            throw new Exception("LUD16 not loaded", 1);
        }

        $zapRequestEvent = $this->generateZapRequestEvent($amount);
        $encodedZapRequestEvent = rawurlencode($zapRequestEvent);

        $response = file_get_contents($this->callbackUrl . "?amount=$amount&event=$encodedZapRequestEvent&lnurl=$this->address");
        $json = json_decode($response, true);

        return $json['pr'];
    }

    public function generateZapRequestEvent(int $amount) {
        $senderPubkey = "";
        $event = (object) [
            "kind" => 9734,
            "content" => "",
            "pubkey" => $senderPubkey,
            "created_at" => time(),
            "tags" => [
                ["relays", $this->RELAY_URL],
                ["amount", $amount],
                ["lnurl", $this->address],
                ["p", $this->nostrPubkey],
            ]
        ];

        // sign event

        return $event;
    }

    public function toJSON() {
        return (object) [
            "callback" => $this->callbackUrl,
            "nostrPubkey" => $this->nostrPubkey,
            "allowsNostr" => $this->allowsNostr,
            "federationId" => $this->federationId,
            "accountPubKey" => $this->accountPubKey,
            "minSendable" => $this->minSendable,
            "maxSendable" => $this->maxSendable
        ]
    }


    static function generateUrl($address) {
        $parts = explode('@', $email);

        if (count($parts) !== 2) {
            throw new Exception("Invalid address", 1);
        }
        $username = $parts[0];
        $domain = $parts[1];

        return "https://$domain/.well-known/lnurlp/$username";
    }
}