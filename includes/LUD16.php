<?php

use swentel\nostr\Event\Event;
use swentel\nostr\Sign\Sign;
use swentel\nostr\Key\Key;

const RELAY_URL = "wss://relay.lawallet.ar";

class LUD16 {
    protected $address;
    protected $callbackUrl;
    protected $allowsNostr;
    protected $nostrPubkey;
    protected $federationId;
    protected $accountPubKey;
    protected $minSendable;
    protected $maxSendable;
    protected $senderPubkey;
    protected $orderKey;
    protected $senderPrivkey;
    protected $relays = [RELAY_URL];

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

        if (!isset($json['callback'])) {
            throw new Exception("callback not found in LUD16", 1);
        }

        $instance->fromJSON((object) $json);
        return $instance;
    }

    public function fromJSON(stdClass $json) {
        $this->address = $json->address;
        $this->callbackUrl = $json->callback;
        $this->nostrPubkey = $json->nostrPubkey;
        $this->allowsNostr = $json->allowsNostr;
        $this->federationId = $json->federationId;
        $this->accountPubKey = $json->accountPubKey;
        $this->minSendable = $json->minSendable;
        $this->maxSendable = $json->maxSendable;

        $this->loaded = true;
    }

    public function createInvoice(int $amount, $orderKey=null) {
        if (!$this->loaded) {
            throw new Exception("LUD16 not loaded", 1);
        }

        $zapRequestEvent = $this->generateZapRequestEvent($amount, $orderKey);
        $encodedZapRequestEvent = urlencode((string) $zapRequestEvent->toJson());

        $url = $this->callbackUrl . "?amount=$amount&nostr=$encodedZapRequestEvent&lnurl=$this->address";

        $response = file_get_contents($url);
        $json = json_decode($response, true);

        $this->orderKey = $orderKey;

        return $json['pr'];
    }

    public function generateZapRequestEvent(int $amount, string $eventId=null) {
        // TODO: Create private key once
        $key = new Key();
        $private_key = $key->generatePrivateKey();

        $this->senderPubkey = $key->getPublicKey($private_key);

        // Generate Event
        $event = new Event();
        $event->setKind(9734);
        $event->setContent('');
        $event->addTag(['relays', ...$this->relays]);
        $event->addTag(['amount', (string) $amount]);
        $event->addTag(['lnurl', $this->address]);
        $event->addTag(['p', $this->nostrPubkey]);
        if ($eventId) {
            $event->addTag(['e', $eventId]);
        }

        // sign event
        $signer = new Sign();
        $signer->signEvent($event, $private_key);

        return $event;
    }

    public function toJson() {
        return (object) [
            "callback" => $this->callbackUrl,
            "nostrPubkey" => $this->nostrPubkey,
            "allowsNostr" => $this->allowsNostr,
            "federationId" => $this->federationId,
            "accountPubKey" => $this->accountPubKey,
            "minSendable" => $this->minSendable,
            "maxSendable" => $this->maxSendable,
            "senderPubkey" => $this->senderPubkey,
            "orderKey" => $this->orderKey,
            "relays" => $this->relays,
        ];
    }


    static function generateUrl($address) {
        $parts = explode('@', $address);

        if (count($parts) !== 2) {
            throw new Exception("Invalid address '$address'", 1);
        }
        $username = $parts[0];
        $domain = $parts[1];

        return "https://$domain/.well-known/lnurlp/$username";
    }
}